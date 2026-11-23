<?php

namespace App\Services\Reports;

use App\Models\Credit;
use App\Models\Customer;
use App\Support\ElSalvadorCatalogo;
use App\Support\PythonBinary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * El Excel de cada cliente: una hoja por persona con sus datos, sus créditos, sus
 * cuotas y sus pagos.
 *
 * Los datos se arman acá (PHP) y el archivo lo escribe python/customer_statement.py,
 * que es el mismo camino de la plantilla de boletas: así el formato vive en un solo
 * lugar y no se reescribe la librería de Excel en el panel.
 */
final class CustomerStatementService
{
    /**
     * Créditos de todo el taller que tienen cuotas vencidas (lo del widget).
     */
    public function overdueCredits(): Collection
    {
        return Credit::query()
            ->overdue()
            ->with(['customer.tags'])
            ->orderByDesc('pending_balance')
            ->get();
    }

    /**
     * Todos los créditos de una persona, vencidos o al día.
     */
    public function customerCredits(Customer $customer): Collection
    {
        return Credit::query()
            ->where('customer_id', $customer->id)
            ->with(['customer.tags'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Genera el archivo y devuelve su ruta.
     *
     * @param  Collection<int, Credit>  $credits
     */
    public function build(Collection $credits): string
    {
        if ($credits->isEmpty()) {
            throw new RuntimeException(__('ui.statement.nothing'));
        }

        $carpeta = storage_path('app/private/estados-de-cuenta');
        @mkdir($carpeta, 0775, true);

        $marca = uniqid('estado-', true);
        $json = $carpeta . '/data-' . $marca . '.json';
        $xlsx = $carpeta . '/estados-de-cuenta-' . $marca . '.xlsx';

        file_put_contents($json, json_encode($this->payload($credits), JSON_UNESCAPED_UNICODE));

        $proceso = new Process([
            PythonBinary::path(),
            base_path('python/customer_statement.py'),
            $json,
            $xlsx,
        ]);

        $proceso->setTimeout(120);
        $proceso->run();

        @unlink($json);

        if (! $proceso->isSuccessful() || ! is_file($xlsx)) {
            throw new RuntimeException(
                __('ui.statement.failed') . ' ' . trim($proceso->getErrorOutput() . ' ' . $proceso->getOutput())
            );
        }

        return $xlsx;
    }

    /**
     * Lo que consume el script de python (y lo que prueban los tests).
     *
     * @param  Collection<int, Credit>  $credits
     * @return array<string, mixed>
     */
    public function payload(Collection $credits): array
    {
        $cuentas = [];

        foreach ($credits->groupBy('customer_id') as $creditosCliente) {
            $cliente = $creditosCliente->first()->customer;

            if (! $cliente) {
                continue;
            }

            $cuentas[] = [
                'cliente' => $this->customerData($cliente),
                'vendedor' => $this->salespersonOf($cliente),
                'resumen' => $this->summary($creditosCliente),
                'creditos' => $creditosCliente->map(fn (Credit $credito): array => [
                    'credito' => $this->creditData($credito),
                    'articulos' => $this->items($credito),
                    'resumen' => $this->summary(collect([$credito])),
                    'cuotas' => $this->installments($credito),
                    'pagos' => $this->payments($credito),
                ])->values()->all(),
            ];
        }

        usort($cuentas, fn (array $a, array $b): int => $b['resumen']['saldo_vencido'] <=> $a['resumen']['saldo_vencido']);

        return [
            'generado' => now()->format('d/m/Y H:i'),
            'vendedores' => $this->bySalesperson($cuentas),
            'clientes' => $cuentas,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customerData(Customer $cliente): array
    {
        return [
            'id' => $cliente->id,
            'nombre' => $cliente->full_name,
            'documento' => trim(($cliente->document_type ?? '') . ' ' . ($cliente->document_number ?? '')) ?: '—',
            'telefono' => $cliente->phone_primary ?? '—',
            'telefono_alt' => $cliente->phone_secondary,
            'correo' => $cliente->email ?? '—',
            'direccion' => $cliente->address ?? '—',
            'departamento' => ElSalvadorCatalogo::resolveDepartment($cliente->department),
            'municipio' => ElSalvadorCatalogo::resolveMunicipality($cliente->department, $cliente->municipality),
            'etiquetas' => $cliente->tags?->pluck('name')->all() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditData(Credit $credito): array
    {
        return [
            'id' => $credito->id,
            'estado' => $credito->status,
            'monto_inicial' => (float) $credito->initial_amount,
            'prima' => (float) $credito->down_payment,
            'financiado' => (float) $credito->financed_amount,
            'cuotas' => (int) $credito->installments,
            'cuota_monto' => (float) $credito->installment_amount,
            'tasa' => (float) $credito->interest_rate,
            'interes' => (float) $credito->total_interest,
            'total' => (float) $credito->total_amount,
            'saldo' => (float) $credito->pending_balance,
            'inicio' => $credito->start_date?->format('d/m/Y'),
            'dia_pago' => $credito->payment_day,
            'periodicidad' => $credito->periodicity,
            'notas' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(Credit $credito): array
    {
        return $credito->items()->get()->map(fn ($item): array => [
            'descripcion' => $item->displayName ?? ('Ítem #' . $item->item_id),
            'cantidad' => (int) $item->quantity,
            'precio' => (float) $item->price,
        ])->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function installments(Credit $credito): array
    {
        $hoy = now()->startOfDay();

        // `installments` también es la columna con el número de cuotas: relación con ().
        return $credito->installments()->get()
            ->sortBy('number')
            ->map(fn ($cuota): array => [
                'numero' => $cuota->number,
                'vence' => $cuota->due_date?->format('d/m/Y'),
                'monto' => (float) $cuota->amount,
                'pagado' => round((float) $cuota->amount - (float) $cuota->remaining_balance, 2),
                'saldo' => (float) $cuota->remaining_balance,
                'estado' => $cuota->status,
                'dias_atraso' => $cuota->due_date && in_array($cuota->status, ['pending', 'late'], true)
                    ? max(0, (int) $cuota->due_date->copy()->startOfDay()->diffInDays($hoy, false))
                    : 0,
            ])->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function payments(Credit $credito): array
    {
        return $credito->paymentHistories()->with(['bank', 'user'])->get()
            ->sortBy('payment_date')
            ->map(fn ($pago): array => [
                'fecha' => $pago->payment_date?->format('d/m/Y'),
                'monto' => (float) $pago->amount,
                'metodo' => $pago->payment_method,
                'banco' => $pago->bank?->name,
                'comprobante' => $pago->receipt_number,
                'usuario' => $pago->user?->name,
                'nota' => $pago->notes,
            ])->values()->all();
    }

    /**
     * Totales de una lista de créditos: lo que se debe y lo que está vencido.
     *
     * @param  Collection<int, Credit>  $creditos
     * @return array<string, float|int>
     */
    private function summary(Collection $creditos): array
    {
        $vencido = 0.0;
        $vencidas = 0;
        $pagado = 0.0;
        $cuotas = 0;
        $pagadas = 0;

        foreach ($creditos as $credito) {
            $cuotasCredito = $credito->installments()->get();
            $cuotas += $cuotasCredito->count();

            foreach ($cuotasCredito as $cuota) {
                $pagado += (float) $cuota->amount - (float) $cuota->remaining_balance;

                if ($cuota->status === 'paid') {
                    $pagadas++;
                    continue;
                }

                if ($cuota->due_date && $cuota->due_date->isPast()) {
                    $vencidas++;
                    $vencido += (float) $cuota->remaining_balance;
                }
            }
        }

        return [
            'creditos' => $creditos->count(),
            'total' => round((float) $creditos->sum('total_amount'), 2),
            'pagado' => round($pagado, 2),
            'saldo' => round((float) $creditos->sum('pending_balance'), 2),
            'cuotas' => $cuotas,
            'cuotas_pagadas' => $pagadas,
            'vencidas' => $vencidas,
            'saldo_vencido' => round($vencido, 2),
        ];
    }

    /**
     * El vendedor que atendió al cliente: el de su última entrada de caja.
     */
    private function salespersonOf(Customer $cliente): ?string
    {
        return $cliente->inflows()
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->latest('date')
            ->value('salesperson');
    }

    /**
     * Resumen por vendedor: cuántos clientes, cuánto debe y cuánto tiene vencido.
     *
     * @param  array<int, array<string, mixed>>  $cuentas
     * @return array<int, array<string, mixed>>
     */
    private function bySalesperson(array $cuentas): array
    {
        $filas = [];

        foreach ($cuentas as $cuenta) {
            $llave = $cuenta['vendedor'] ?: __('ui.statement.no_salesperson');

            $filas[$llave] ??= [
                'vendedor' => $llave,
                'clientes' => 0,
                'creditos' => 0,
                'total' => 0.0,
                'pagado' => 0.0,
                'saldo' => 0.0,
                'vencidas' => 0,
                'saldo_vencido' => 0.0,
            ];

            $filas[$llave]['clientes']++;
            $filas[$llave]['creditos'] += $cuenta['resumen']['creditos'];
            $filas[$llave]['total'] += $cuenta['resumen']['total'];
            $filas[$llave]['pagado'] += $cuenta['resumen']['pagado'];
            $filas[$llave]['saldo'] += $cuenta['resumen']['saldo'];
            $filas[$llave]['vencidas'] += $cuenta['resumen']['vencidas'];
            $filas[$llave]['saldo_vencido'] += $cuenta['resumen']['saldo_vencido'];
        }

        foreach ($filas as &$fila) {
            foreach (['total', 'pagado', 'saldo', 'saldo_vencido'] as $campo) {
                $fila[$campo] = round($fila[$campo], 2);
            }
        }

        $filas = array_values($filas);

        usort($filas, fn (array $a, array $b): int => $b['saldo_vencido'] <=> $a['saldo_vencido']);

        return $filas;
    }
}
