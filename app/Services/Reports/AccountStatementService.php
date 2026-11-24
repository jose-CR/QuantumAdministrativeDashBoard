<?php

namespace App\Services\Reports;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\PaymentHistory;
use App\Support\DocumentTypes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Estado de cuenta de un cliente: todas sus facturas contra todos sus pagos.
 *
 * La regla del documento: **la factura es el cargo y los pagos son los abonos**. Las
 * cuotas de una venta a crédito no suman aparte — son el plan de pago de esa factura y
 * si se sumaran, el cliente debería el doble.
 *
 * Una factura de contado vence el día que se emite; por eso el vencimiento de una
 * factura es su propia fecha. Cuando exista el vínculo factura → crédito, ese
 * vencimiento pasa a ser el de la primera cuota impaga.
 */
final class AccountStatementService
{
    /**
     * Arma todo lo que el documento necesita mostrar.
     *
     * @return array<string, mixed>
     */
    public function forCustomer(Customer $customer): array
    {
        $facturas = $this->invoices($customer);
        $creditos = $this->credits($customer);

        $resumen = $this->summary($facturas, $creditos);
        $hoy = now()->startOfDay();
        // El período es el historial completo: de la factura más vieja a la más nueva.
        $fechas = $facturas->pluck('date')->filter()->sort();

        return [
            'generado' => now()->format('d/m/Y H:i'),
            'estado' => [
                'numero' => 'EC-' . now()->format('Y') . '-' . str_pad((string) $customer->getKey(), 4, '0', STR_PAD_LEFT),
                'emision' => now()->format('d/m/Y'),
                'desde' => $fechas->first()?->format('d/m/Y'),
                'hasta' => $fechas->last()?->format('d/m/Y'),
                'moneda' => 'USD',
            ],
            'cliente' => $this->customerData($customer),
            'resumen' => $resumen,
            'aging' => $this->aging($facturas, $creditos, $hoy),
            'facturas' => $facturas->map(fn (Inflow $factura): array => $this->invoiceRow($factura, $creditos, $hoy))->values()->all(),
            'planes' => $this->paymentPlans($customer, $creditos),
            'recibos' => $this->receipts($customer),
        ];
    }

    /**
     * Todas las facturas del cliente: primero las que tienen saldo, de la más vieja a
     * la más nueva; después las pagadas, de la más reciente a la más vieja.
     *
     * @return Collection<int, Inflow>
     */
    private function invoices(Customer $customer): Collection
    {
        $facturas = Inflow::query()
            ->where('customer_id', $customer->getKey())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $conSaldo = $facturas->filter(fn (Inflow $f): bool => $this->balance($f) > 0);
        $pagadas = $facturas->reject(fn (Inflow $f): bool => $this->balance($f) > 0)->sortByDesc('date');

        return $conSaldo->concat($pagadas)->values();
    }

    /**
     * @return array<string, float|int>
     */
    private function summary(Collection $facturas, Collection $creditos): array
    {
        $hoy = now()->startOfDay();

        $facturado = (float) $facturas->sum(fn (Inflow $f): float => (float) $f->amount);
        $abonado = (float) $facturas->sum(fn (Inflow $f): float => (float) $f->amount_paid);
        $saldo = round($facturado - $abonado, 2);

        // Lo vencido sale de la fecha de cada cuota cuando la venta fue a crédito: si no,
        // las cuotas que todavía no vencen se cobrarían como atrasadas.
        $vencido = (float) $facturas->sum(fn (Inflow $f): float => $this->overduePart($f, $creditos, $hoy));

        return [
            'facturado' => round($facturado, 2),
            'abonado' => round($abonado, 2),
            'saldo' => $saldo,
            'vencido' => round($vencido, 2),
            'por_vencer' => round($saldo - $vencido, 2),
            'facturas' => $facturas->count(),
            'pagadas' => $facturas->reject(fn (Inflow $f): bool => $this->balance($f) > 0)->count(),
            'con_saldo' => $facturas->filter(fn (Inflow $f): bool => $this->balance($f) > 0)->count(),
        ];
    }

    /**
     * Antigüedad del saldo: cuánto de lo que se debe lleva más tiempo vencido.
     *
     * @return array<string, float>
     */
    private function aging(Collection $facturas, Collection $creditos, Carbon $hoy): array
    {
        $tramos = ['d1_30' => 0.0, 'd31_60' => 0.0, 'd61_90' => 0.0, 'd90' => 0.0];

        foreach ($facturas as $factura) {
            $saldo = $this->balance($factura);

            if ($saldo <= 0) {
                continue;
            }

            $credito = $this->creditFor($factura, $creditos);

            // A crédito: cada cuota vencida cae en su tramo por su propia fecha.
            if ($credito) {
                $credito->installments()->get()
                    ->filter(fn ($cuota): bool => (float) $cuota->remaining_balance > 0 && $cuota->due_date?->lt($hoy))
                    ->each(function ($cuota) use (&$tramos, $hoy): void {
                        $tramo = $this->tranche($cuota->due_date, $hoy);

                        if ($tramo !== null) {
                            $tramos[$tramo] += (float) $cuota->remaining_balance;
                        }
                    });

                continue;
            }

            $tramo = $this->tranche($factura->date, $hoy);

            if ($tramo !== null) {
                $tramos[$tramo] += $saldo;
            }
        }

        $tramos = array_map(fn (float $monto): float => round($monto, 2), $tramos);
        $tramos['total'] = round(array_sum($tramos), 2);

        return $tramos;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceRow(Inflow $factura, Collection $creditos, Carbon $hoy): array
    {
        $saldo = $this->balance($factura);
        $vencimiento = $this->dueDate($factura, $creditos);

        return [
            'numero' => (string) $factura->invoice_number,
            'fecha' => $factura->date?->format('d/m/Y'),
            // Contado: vence el día que se emite. A crédito: en la primera cuota impaga.
            'vence' => $vencimiento?->format('d/m/Y'),
            'concepto' => (string) ($factura->description ?: '—'),
            // El papel que tiene el cliente dice CF / CCF, no el nombre largo.
            'tipo' => mb_strtoupper((string) $factura->document_type),
            'cargo' => round((float) $factura->amount, 2),
            'abono' => round((float) $factura->amount_paid, 2),
            'saldo' => $saldo,
            'estado' => $this->state($factura),
            'dias' => $saldo > 0 && $vencimiento
                ? max(0, (int) $vencimiento->copy()->startOfDay()->diffInDays($hoy, false))
                : null,
        ];
    }

    /**
     * El estado sale de los números, nunca de una marca escrita a mano: una factura
     * «pagada» con saldo es justo lo que hacía mentir al estado de cuenta anterior.
     */
    private function state(Inflow $factura): string
    {
        if ($this->balance($factura) <= 0) {
            return 'pagado';
        }

        return (float) $factura->amount_paid > 0 ? 'parcial' : 'pendiente';
    }

    private function balance(Inflow $factura): float
    {
        return round((float) $factura->amount - (float) $factura->amount_paid, 2);
    }

    /**
     * Plan de pago de lo que se compró a crédito. No suma al saldo: es la forma de
     * pagar una factura que ya está en el cuadro de arriba.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentPlans(Customer $customer, Collection $creditos): array
    {
        $planes = [];

        foreach ($creditos as $credito) {
            // `installments` también es la columna con el número de cuotas: relación con
            // paréntesis o devuelve un entero.
            $cuotas = $credito->installments()->get()->sortBy('number');

            if ($cuotas->isEmpty()) {
                continue;
            }

            $planes[] = [
                'referencia' => __('ui.statement.credit') . ' #' . $credito->getKey(),
                // Si una factura del cliente tiene el mismo monto, es casi seguro la que
                // originó el crédito: se dice, pero no se da por hecho.
                'factura' => $this->matchingInvoiceNumber($customer, (float) $credito->total_amount, $creditos),
                'cuotas' => $cuotas->map(fn ($cuota): array => [
                    'numero' => $cuota->number,
                    'vence' => $cuota->due_date?->format('d/m/Y'),
                    'monto' => round((float) $cuota->amount, 2),
                    'pagado' => round((float) $cuota->amount - (float) $cuota->remaining_balance, 2),
                    'saldo' => round((float) $cuota->remaining_balance, 2),
                    'estado' => $cuota->status,
                ])->values()->all(),
                'total' => round((float) $cuotas->sum('amount'), 2),
                'saldo' => round((float) $cuotas->sum('remaining_balance'), 2),
            ];
        }

        return $planes;
    }

    /**
     * La factura del crédito, con el mismo criterio que `creditFor()`: solo si es única.
     */
    private function matchingInvoiceNumber(Customer $customer, float $monto, Collection $creditos): ?string
    {
        if ($monto <= 0) {
            return null;
        }

        $facturas = Inflow::query()
            ->where('customer_id', $customer->getKey())
            ->whereBetween('amount', [$monto - 0.01, $monto + 0.01])
            ->get();

        return $facturas->count() === 1 ? (string) $facturas->first()->invoice_number : null;
    }

    /**
     * Recibos: los pagos registrados, con su comprobante, banco y quién los cargó.
     *
     * @return array<int, array<string, mixed>>
     */
    private function receipts(Customer $customer): array
    {
        return PaymentHistory::query()
            ->whereHas('credit', fn ($query) => $query->where('customer_id', $customer->getKey()))
            ->with(['bank', 'user', 'credit'])
            ->orderBy('payment_date')
            ->get()
            ->map(fn (PaymentHistory $pago): array => [
                'numero' => $pago->receipt_number ?: ('#' . $pago->getKey()),
                'fecha' => $pago->payment_date?->format('d/m/Y'),
                'detalle' => $pago->notes ?: __('ui.statement.credit_payment'),
                'aplicado' => __('ui.statement.credit') . ' #' . $pago->credit_id,
                'monto' => round((float) $pago->amount, 2),
                'metodo' => __('ui.payment_methods.' . $pago->payment_method),
                'banco' => $pago->bank?->name,
                'usuario' => $pago->user?->name,
            ])->values()->all();
    }

    /**
     * Créditos con cuotas pendientes del cliente.
     *
     * @return Collection<int, Credit>
     */
    private function credits(Customer $customer): Collection
    {
        return Credit::query()
            ->where('customer_id', $customer->getKey())
            ->whereIn('status', ['active', 'late', 'pending'])
            ->orderBy('id')
            ->get();
    }

    /**
     * El crédito que originó la factura.
     *
     * No hay columna que los una: se reconoce por el monto (el total del crédito es el
     * de la factura) y solo cuando hay UN candidato. Si hay dos, no se adivina.
     */
    private function creditFor(Inflow $factura, Collection $creditos): ?Credit
    {
        $candidatos = $creditos->filter(
            fn (Credit $credito): bool => abs((float) $credito->total_amount - (float) $factura->amount) <= 0.01
        );

        return $candidatos->count() === 1 ? $candidatos->first() : null;
    }

    /**
     * Cuándo vence la factura: la primera cuota impaga si fue a crédito, si no su fecha.
     */
    private function dueDate(Inflow $factura, Collection $creditos): ?Carbon
    {
        $credito = $this->creditFor($factura, $creditos);

        if (! $credito) {
            return $factura->date;
        }

        $cuotas = $credito->installments()->get()->sortBy('due_date');

        $impaga = $cuotas->first(fn ($cuota): bool => (float) $cuota->remaining_balance > 0);

        return $impaga?->due_date ?? $cuotas->first()?->due_date ?? $factura->date;
    }

    /**
     * Lo que de esa factura está vencido hoy.
     */
    private function overduePart(Inflow $factura, Collection $creditos, Carbon $hoy): float
    {
        $saldo = $this->balance($factura);

        if ($saldo <= 0) {
            return 0.0;
        }

        $credito = $this->creditFor($factura, $creditos);

        if (! $credito) {
            return $factura->date && $factura->date->lt($hoy) ? $saldo : 0.0;
        }

        // A crédito: solo las cuotas ya vencidas, y nunca más que el saldo de la factura.
        $vencido = (float) $credito->installments()->get()
            ->filter(fn ($cuota): bool => $cuota->due_date?->lt($hoy))
            ->sum(fn ($cuota): float => (float) $cuota->remaining_balance);

        return round(min($vencido, $saldo), 2);
    }

    /**
     * Tramo de antigüedad de una fecha de vencimiento.
     */
    private function tranche(?Carbon $vence, Carbon $hoy): ?string
    {
        if (! $vence) {
            return null;
        }

        $dias = (int) $vence->copy()->startOfDay()->diffInDays($hoy, false);

        return match (true) {
            $dias <= 0 => null,          // todavía no vence
            $dias <= 30 => 'd1_30',
            $dias <= 60 => 'd31_60',
            $dias <= 90 => 'd61_90',
            default => 'd90',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function customerData(Customer $customer): array
    {
        return [
            'nombre' => $customer->full_name,
            'documento' => trim(($customer->document_type ?? '') . ' ' . ($customer->document_number ?? '')) ?: '—',
            'telefono' => $customer->phone_primary ?: '—',
            'correo' => $customer->email ?: '—',
            'direccion' => $customer->address ?: '—',
        ];
    }
}
