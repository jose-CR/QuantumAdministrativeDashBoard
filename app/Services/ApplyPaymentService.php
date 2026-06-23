<?php

namespace App\Services;

use App\Models\Credit;
use Illuminate\Support\Facades\DB;

class ApplyPaymentService
{
    public function apply(
        Credit $credit,
        float $amount
    ): void {

        DB::transaction(function () use (
            $credit,
            $amount
        ) {

            $remainingPayment = $amount;

            $installments = $credit
                ->installments()
                ->where('status', '!=', 'paid')
                ->orderBy('number')
                ->get();

            foreach ($installments as $installment) {

                if ($remainingPayment <= 0) {
                    break;
                }

                $balance = $installment->remaining_balance;

                // Pago completo de la cuota
                if ($remainingPayment >= $balance) {

                    $remainingPayment -= $balance;

                    $installment->update([
                        'remaining_balance' => 0,
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    continue;
                }

                // Pago parcial
                $installment->update([
                    'remaining_balance' => (
                        $balance - $remainingPayment
                    ),
                ]);

                $remainingPayment = 0;
            }

            $credit->update([
                'pending_balance' => $credit
                    ->installments()
                    ->sum('remaining_balance'),
            ]);
        });
    }
}