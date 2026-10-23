<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Credit;
use App\Models\Installment;

class InstallmentGeneratorService
{
    public function generate(Credit $credit): void
    {
        $credit->loadMissing('installments');

        if ($credit->installments()->exists()) {
            return;
        }

        $dueDate = Carbon::parse($credit->start_date);

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
                'due_date'          => $dueDate->copy(),
                'status'            => 'pending',
            ]);

            match ($credit->periodicity) {
                'weekly' => $dueDate->addWeek(),
                'monthly' => $dueDate->addMonth(),
                'yearly' => $dueDate->addYear(),
            };
        }
    }
}