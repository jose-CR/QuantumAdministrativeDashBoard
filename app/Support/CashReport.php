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
            throw new \RuntimeException(__('ui.cash_report.no_data'));
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

        throw_if($result->failed(), \RuntimeException::class, $result->errorOutput() ?: __('ui.cash_report.python_failed'));

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

        $method = [
            'cash' => __('ui.payment_methods.cash'),
            'transfer' => __('ui.payment_methods.transfer'),
        ];
        $status = [
            'pending' => __('ui.payment_status.pending'),
            'partial' => __('ui.payment_status.partial'),
            'paid' => __('ui.payment_status.paid'),
        ];

        return [
            'title' => __('ui.widgets.inflows'),
            'headers' => [
                __('ui.columns.invoice_number'), __('ui.columns.date'), __('ui.columns.description'), __('ui.columns.customer'), __('ui.columns.amount'), __('ui.columns.collected'), __('ui.columns.receivable'),
                __('ui.columns.payment_method'), __('ui.columns.transfer_number'), __('ui.columns.bank'), __('ui.columns.transfer_date'),
                __('ui.columns.payment_status'), __('ui.columns.salesperson'), __('ui.columns.notes'),
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
            'title' => __('ui.widgets.outflows'),
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
            static::portfolio(),
            static::inventory(),
        ];
    }

    private static function portfolio(): array
    {
        $customers = Customer::query()
            ->whereHas('credits', fn ($query) => $query->where('status', 'active'))
            ->with(['credits' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('full_name')
            ->get();

        $today = today();

        $rows = $customers->map(function (Customer $customer) use ($today): array {
            $credits = $customer->credits;

            $balance = round((float) $credits->sum('pending_balance'), 2);

            $installments = Installment::query()
                ->whereIn('credit_id', $credits->pluck('id'))
                ->get();

            $overdue = round((float) $installments
                ->whereIn('status', Installment::UNPAID_STATUSES)
                ->filter(fn (Installment $installment): bool => $installment->due_date?->lt($today))
                ->sum('remaining_balance'), 2);

            $next = $installments
                ->whereIn('status', Installment::UNPAID_STATUSES)
                ->min(fn (Installment $installment) => $installment->due_date);

            return [
                $customer->full_name,
                $credits->count(),
                $balance,
                $overdue,
                $next?->toDateString(),
            ];
        })->all();

        return [
            'title' => 'Cartera',
            'headers' => ['Cliente', 'Créditos activos', 'Saldo por cobrar', 'Vencido', 'Próximo vencimiento'],
            'rows' => $rows,
        ];
    }

    private static function inventory(): array
    {
        $articles = Article::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $rows = $articles->map(function (Article $article): array {
            $isSerial = $article->tracksSerials();

            $available = $isSerial
                ? $article->units()->where('status', 'available')->count()
                : (int) $article->stock;

            return [
                $article->category?->name,
                $article->full_name,
                $isSerial ? 'Con serie' : 'Por cantidad',
                $available,
                (float) $article->cash_price,
                round($available * (float) $article->cash_price, 2),
            ];
        })->all();

        return [
            'title' => 'Inventario',
            'headers' => ['Categoría', 'Artículo', 'Tipo', 'Disponibles', 'Precio', 'Valor'],
            'rows' => $rows,
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