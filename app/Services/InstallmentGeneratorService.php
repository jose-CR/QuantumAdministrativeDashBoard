<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Credit;
use App\Models\Installment;
use App\Models\User;
use App\Services\Alerts\AlertService;

class InstallmentGeneratorService
{
    public function generate(Credit $credit, User $creator, ?User $assignedUser,): void
    {
        $credit->loadMissing('installments');

        if ($credit->installments()->exists()) {
            return;
        }

        $dueDate = Carbon::parse($credit->start_date);

        $amount = (float) $credit->installment_amount;

        $alertService = app(AlertService::class);

        for ($i = 1; $i <= $credit->installments; $i++) {

            $installment = Installment::create([
                'credit_id' => $credit->id,
                'number' => $i,
                'amount' => $amount,
                'remaining_balance' => $amount,
                'due_date' => $dueDate->copy(),
                'status' => 'pending',
            ]);

            $alertService->createUpcoming(
                installment: $installment,
                creator: $creator,
                assignedUser: $assignedUser,
            );

            match ($credit->periodicity) {
                'weekly' => $dueDate->addWeek(),
                'monthly' => $dueDate->addMonth(),
                'yearly' => $dueDate->addYear(),
            };
        }
    }
}
