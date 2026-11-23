<?php

namespace App\Support\Cash;

use App\Models\Inflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Las facturas detrás de los números «Cobrado» y «Por cobrar» del tablero.
 *
 * Cobrado  = lo que ya entró de las facturas del rango (amount_paid).
 * Por cobrar = lo que falta de esas mismas facturas (amount - amount_paid).
 *
 * Se devuelve el detalle porque los totales solos no dicen qué factura es cuál.
 */
final class CashInvoices
{
    /**
     * @return array{collected: array{count: int, amount: float, invoices: array<int, array<string, mixed>>}, pending: array{count: int, amount: float, invoices: array<int, array<string, mixed>>}}
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        [$desde, $hasta] = $this->range($from, $to);

        $cobradas = $this->inRange($desde, $hasta)
            ->where('amount_paid', '>', 0)
            ->orderByDesc('date')
            ->get();

        $porCobrar = $this->inRange($desde, $hasta)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('amount_paid')
                ->orWhereColumn('amount_paid', '<', 'amount'))
            ->orderByDesc('date')
            ->get();

        return [
            'collected' => [
                'count' => $cobradas->count(),
                'amount' => round((float) $cobradas->sum('amount_paid'), 2),
                'invoices' => $cobradas->map(fn (Inflow $entrada): array => $this->row($entrada))->all(),
            ],
            'pending' => [
                'count' => $porCobrar->count(),
                'amount' => round((float) $porCobrar->sum(fn (Inflow $e): float => (float) $e->amount - (float) $e->amount_paid), 2),
                'invoices' => $porCobrar->map(fn (Inflow $entrada): array => $this->row($entrada))->all(),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $desde  no usado: se mantiene la firma por claridad
     * @return array<string, mixed>
     */
    private function row(Inflow $entrada): array
    {
        $total = (float) $entrada->amount;
        $cobrado = (float) $entrada->amount_paid;

        return [
            'invoice' => $entrada->invoice_number,
            'date' => $entrada->date?->format('d/m/Y'),
            'customer' => $entrada->customer?->full_name ?? '—',
            'amount' => round($total, 2),
            'paid' => round($cobrado, 2),
            'balance' => round(max($total - $cobrado, 0), 2),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function range(?string $from, ?string $to): array
    {
        $inicio = $from ? Carbon::parse($from) : now()->startOfMonth();
        $fin = $to ? Carbon::parse($to) : now()->endOfMonth();

        return [$inicio->startOfDay()->toDateString(), $fin->endOfDay()->toDateString()];
    }

    /**
     * @return Builder<Inflow>
     */
    private function inRange(string $desde, string $hasta): Builder
    {
        return Inflow::query()
            ->with('customer')
            ->whereDate('date', '>=', $desde)
            ->whereDate('date', '<=', $hasta);
    }
}
