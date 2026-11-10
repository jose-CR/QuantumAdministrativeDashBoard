<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\Article;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Installment;
use App\Support\PythonBinary;
use App\Models\Outflow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class CashReport
{
    /**
     * Earliest/latest date with data across inflows and outflows, used to
     * bound the DatePicker so the user can't pick a month with nothing in it.
     */
    public static function dateRange(): array
    {
        $min = collect([
            Inflow::min('date'),
            Outflow::min('invoice_date'),
        ])->filter()->min();

        $max = collect([
            Inflow::max('date'),
            Outflow::max('invoice_date'),
        ])->filter()->max();

        return [
            'min' => $min ? Carbon::parse($min)->toDateString() : null,
            'max' => $max ? Carbon::parse($max)->toDateString() : null,
        ];
    }

    public static function generate(?string $from = null, ?string $until = null): string
    {
        $inflows = static::inflows($from, $until);
        $outflows = static::outflows($from, $until);

        if (empty($inflows['rows']) && empty($outflows['rows'])) {
            throw new \RuntimeException('No hay entradas ni salidas registradas en ese rango de fechas.');
        }

        $out = storage_path('app/private/exports/cash_report_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.xlsx');
        File::ensureDirectoryExists(dirname($out));

        $payload = [
            'out' => $out,
            'gap' => 2,
            'projection_months' => 3,
            'inflows' => $inflows,
            'outflows' => $outflows,
            // Cartera e inventario: van como hojas extra, sin pasar por el
            // resumen de caja (que analiza montos por fecha).
            'extras' => static::extras(),
        ];

        $result = Process::timeout(120)
            ->input(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->run([
                // En Docker no hay venv: PythonBinary resuelve venv, PYTHON_BIN
                // o el python3 del sistema.
                PythonBinary::path(),
                base_path(config('quantum.python.script', 'python/scripts/cash_report.py')),
            ]);

        throw_if($result->failed(), \RuntimeException::class, $result->errorOutput() ?: 'Python falló sin mensaje.');

        return $out;
    }

    private static function inflows(?string $from, ?string $until): array
    {
        $records = Inflow::query()
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->orderBy('date')->orderBy('id')
            ->get();

        $customers = Customer::whereIn('id', $records->pluck('customer_id')->unique())->get()
            ->mapWithKeys(fn (Customer $c) => [$c->id => $c->full_name]);
        $banks = Bank::whereIn('id', $records->pluck('bank_id')->filter()->unique())->pluck('name', 'id');

        $method = ['cash' => 'Efectivo', 'transfer' => 'Transferencia'];
        $status = ['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'];

        return [
            'title' => 'Entradas',
            'headers' => [
                'Número de factura', 'Fecha', 'Descripción', 'Cliente', 'Monto', 'Cobrado', 'Por cobrar',
                'Método de pago', 'Número de transferencia', 'Banco', 'Fecha de transferencia',
                'Estado de pago', 'Vendedor', 'Notas',
            ],
            'rows' => $records->map(fn (Inflow $i) => [
                $i->invoice_number,
                static::date($i->date),
                $i->description,
                $customers[$i->customer_id] ?? null,
                $i->amount,
                (float) $i->amount_paid,
                (float) $i->receivable,
                $method[static::raw($i->payment_method)] ?? static::raw($i->payment_method),
                $i->transfer_number,
                $banks[$i->bank_id] ?? null,
                static::date($i->transfer_date),
                $status[static::raw($i->payment_status)] ?? static::raw($i->payment_status),
                $i->salesperson,
                $i->notes,
            ])->all(),
            'amount' => 'Monto',
            'date' => 'Fecha',
            'invoice' => 'Número de factura',
            'party' => 'Cliente',
            'group_by' => 'Método de pago',
        ];
    }

    private static function outflows(?string $from, ?string $until): array
    {
        $records = Outflow::query()
            ->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('invoice_date', '<=', $until))
            ->orderBy('invoice_date')->orderBy('id')
            ->get();

        return [
            'title' => 'Salidas',
            'headers' => [
                'Fecha de factura', 'Empresa', 'Código de factura', 'Cantidad', 'Monto', 'Descripción', 'Origen', 'Área',
            ],
            'rows' => $records->map(fn (Outflow $o) => [
                static::date($o->invoice_date),
                $o->company,
                $o->invoice_code,
                $o->quantity,
                $o->amount,
                $o->description,
                static::raw($o->source),
                static::raw($o->area),
            ])->all(),
            'amount' => 'Monto',
            'date' => 'Fecha de factura',
            'invoice' => 'Código de factura',
            'party' => 'Empresa',
            'group_by' => 'Área',
        ];
    }

    /**
     * Cartera e inventario: las dos hojas que faltaban para que el reporte cuente
     * la historia completa (lo que se cobra y lo que hay en el depósito).
     *
     * @return array<int, array{title: string, headers: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    private static function extras(): array
    {
        return [
            static::cartera(),
            static::inventario(),
        ];
    }

    private static function cartera(): array
    {
        $clientes = Customer::query()
            ->whereHas('credits', fn ($query) => $query->where('status', 'active'))
            ->with(['credits' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('full_name')
            ->get();

        $hoy = today();

        $filas = $clientes->map(function (Customer $cliente) use ($hoy): array {
            $creditos = $cliente->credits;

            $saldo = round((float) $creditos->sum('pending_balance'), 2);

            $cuotas = Installment::query()
                ->whereIn('credit_id', $creditos->pluck('id'))
                ->get();

            $vencido = round((float) $cuotas
                ->whereIn('status', Installment::UNPAID_STATUSES)
                ->filter(fn (Installment $cuota): bool => $cuota->due_date?->lt($hoy))
                ->sum('remaining_balance'), 2);

            $proxima = $cuotas
                ->whereIn('status', Installment::UNPAID_STATUSES)
                ->min(fn (Installment $cuota) => $cuota->due_date);

            return [
                $cliente->full_name,
                $creditos->count(),
                $saldo,
                $vencido,
                $proxima?->toDateString(),
            ];
        })->all();

        return [
            'title' => 'Cartera',
            'headers' => ['Cliente', 'Créditos activos', 'Saldo por cobrar', 'Vencido', 'Próximo vencimiento'],
            'rows' => $filas,
        ];
    }

    private static function inventario(): array
    {
        $articulos = Article::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $filas = $articulos->map(function (Article $articulo): array {
            $esSerial = $articulo->tracksSerials();

            $disponibles = $esSerial
                ? $articulo->units()->where('status', 'available')->count()
                : (int) $articulo->stock;

            return [
                $articulo->category?->name,
                $articulo->full_name,
                $esSerial ? 'Con serie' : 'Por cantidad',
                $disponibles,
                (float) $articulo->cash_price,
                round($disponibles * (float) $articulo->cash_price, 2),
            ];
        })->all();

        return [
            'title' => 'Inventario',
            'headers' => ['Categoría', 'Artículo', 'Tipo', 'Disponibles', 'Precio', 'Valor'],
            'rows' => $filas,
        ];
    }

    private static function date(mixed $value): ?string
    {
        return filled($value) ? Carbon::parse($value)->toDateString() : null;
    }

    private static function raw(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}