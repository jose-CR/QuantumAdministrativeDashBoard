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

        for ($i = 1; $i <= $credit->installments; $i++) {

            Installment::create([
                'credit_id' => $credit->id,
                'number' => $i,
                'amount' => $amount,
                'remaining_balance' => $amount,
                'due_date' => $dueDate->copy(),
                'status' => 'pending',
            ]);

            match ($credit->periodicity) {
                'weekly' => $dueDate->addWeek(),
                'monthly' => $dueDate->addMonth(),
                'yearly' => $dueDate->addYear(),
            };
        }
    }
}