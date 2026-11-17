<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Installment;

/**
 * Arma las cuotas de un crédito nuevo.
 *
 * Las fechas salen del día de pago pactado (`Credit::dueDateFor`), no del día en que
 * se abrió el crédito: con inicio el 10/11 y día de pago 27, la primera cuota vence
 * el 27/12.
 */
class InstallmentGeneratorService
{
    public function generate(Credit $credit): void
    {
        if ($credit->installments()->exists()) {
            return;
        }

        $amount = (float) $credit->installment_amount;
        $count = (int) $credit->installments;
        $lastAmount = round((float) $credit->total_amount - ($count - 1) * $amount, 2);

        for ($i = 1; $i <= $count; $i++) {
            $current = $i === $count ? $lastAmount : $amount;

            Installment::create([
                'credit_id'         => $credit->id,
                'number'            => $i,
                'amount'            => $current,
                'remaining_balance' => $current,
                'due_date'          => $credit->dueDateFor($i),
                'status'            => 'pending',
            ]);
        }
    }
}
