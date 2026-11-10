<?php

namespace App\Services\Credits;

use App\Models\Credit;
use Illuminate\Support\Facades\DB;

/**
 * Rehace las cuotas de un crédito a partir de sus pagos.
 *
 * Los pagos son la fuente de verdad; las cuotas y el saldo se derivan de ellos.
 * Sirve para corregir el historial cuando un pago se borra o se modifica: antes
 * el crédito quedaba descuadrado para siempre, sin forma de volver atrás.
 */
class RecalculateCreditService
{
    public function execute(Credit $credit): void
    {
        DB::transaction(function () use ($credit): void {
            $credit = Credit::query()->lockForUpdate()->findOrFail($credit->id);

            $pagos = $credit->paymentHistories()
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();

            // Las cuotas canceladas o refinanciadas no se tocan: ya no forman
            // parte de lo que se cobra.
            $cuotas = $credit->installments()
                ->whereNotIn('status', ['cancelled', 'refinanced'])
                ->orderBy('number')
                ->get();

            $pendiente = [];
            $fechaPago = [];

            foreach ($cuotas as $indice => $cuota) {
                $pendiente[$indice] = (float) $cuota->amount;
            }

            // Repartir los pagos en orden, cuota por cuota.
            foreach ($pagos as $pago) {
                $resto = (float) $pago->amount;

                foreach ($cuotas as $indice => $cuota) {
                    if ($resto <= 0) {
                        break;
                    }

                    if ($pendiente[$indice] <= 0) {
                        continue;
                    }

                    $aplicado = min($pendiente[$indice], $resto);

                    $pendiente[$indice] = round($pendiente[$indice] - $aplicado, 2);
                    $resto = round($resto - $aplicado, 2);

                    if ($pendiente[$indice] <= 0) {
                        $fechaPago[$indice] = $pago->payment_date;
                    }
                }
            }

            foreach ($cuotas as $indice => $cuota) {
                $saldo = $pendiente[$indice];

                $cuota->update([
                    'remaining_balance' => $saldo,
                    'status' => match (true) {
                        $saldo <= 0 => 'paid',
                        $cuota->due_date->lt(today()) => 'late',
                        default => 'pending',
                    },
                    'paid_at' => $saldo <= 0 ? ($fechaPago[$indice] ?? null) : null,
                ]);
            }

            $saldo = round((float) $credit->installments()->sum('remaining_balance'), 2);

            $credit->update([
                'pending_balance' => $saldo,
                // Si se cayó un pago, el crédito vuelve a estar activo: quedarse en
                // 'completed' con deuda es justo lo que hay que evitar.
                'status' => $saldo > 0 && $credit->status === 'completed'
                    ? 'active'
                    : $credit->status,
            ]);

            app(CloseCreditService::class)->execute($credit);
        });
    }
}
