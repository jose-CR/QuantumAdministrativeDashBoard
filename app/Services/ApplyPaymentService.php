<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Installment;
use Illuminate\Support\Facades\DB;

class ApplyPaymentService
{
    public function apply(
        Credit $credit,
        float $amount,
        string $mode = 'auto',
        ?int $installmentId = null
    ): void {

        DB::transaction(function () use (
            $credit,
            $amount,
            $mode,
            $installmentId
        ) {

            $credit = Credit::where('id', $credit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($mode === 'single') {

                $this->applyStartingFromInstallment(
                    $credit,
                    $installmentId,
                    $amount
                );

                $this->recalculatePendingBalance(
                    $credit
                );

                return;
            }

            $this->applyAutomatically(
                $credit,
                $amount
            );

            $this->recalculatePendingBalance(
                $credit
            );
        });
    }

    /**
     * Pago automático:
     * distribuye el dinero entre las cuotas pendientes
     * comenzando por la más antigua.
     */
    private function applyAutomatically(
        Credit $credit,
        float $amount
    ): void {

        $remainingPayment = $amount;

        $installments = $credit
            ->installments()
            ->where('status', 'pending')
            ->orderBy('number')
            ->lockForUpdate()
            ->get();

        foreach ($installments as $installment) {

            if ($remainingPayment <= 0) {
                break;
            }

            $balance = $installment->remaining_balance;

            // Pago completo
            if ($remainingPayment >= $balance) {

                $remainingPayment -= $balance;

                $installment->update([
                    'remaining_balance' => 0,
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                continue;
            }

            // Pago parcial (sigue pending)
            $installment->update([
                'remaining_balance' => $balance - $remainingPayment,
            ]);

            $remainingPayment = 0;
        }
    }

    /**
     * Pago dirigido a una cuota específica.
     */
    private function applyStartingFromInstallment(
        Credit $credit,
        ?int $installmentId,
        float $amount
    ): void {

        $remainingPayment = $amount;

        $installment = Installment::findOrFail($installmentId);

        $installments = $installment->credit
            ->installments()
            ->where('status', 'pending')
            ->where('number', '>=', $installment->number)
            ->orderBy('number')
            ->lockForUpdate()
            ->get();

        foreach ($installments as $installment) {

            if ($remainingPayment <= 0) {
                break;
            }

            $balance = $installment->remaining_balance;

            if ($remainingPayment >= $balance) {

                $remainingPayment -= $balance;

                $installment->update([
                    'remaining_balance' => 0,
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                continue;
            }

            $installment->update([
                'remaining_balance' => $balance - $remainingPayment,
            ]);

            $remainingPayment = 0;
        }
    }

    /**
     * Recalcula el saldo pendiente total del crédito.
     */
    private function recalculatePendingBalance(
        Credit $credit
    ): void {

        $total = $credit
            ->installments()
            ->sum('remaining_balance');

        $credit->update([
            'pending_balance' => $total,
        ]);
    }
}
