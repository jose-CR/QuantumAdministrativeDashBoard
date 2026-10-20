<?php

namespace App\Services\Credits;

use App\Models\Credit;
use App\Models\Installment;
use App\Services\InstallmentGeneratorService;
use InvalidArgumentException;

class InstallmentSyncService
{
    private const EDITABLE_STATUSES = ['pending', 'late'];

    public function sync(
        Credit $credit,
        float $oldInstallmentAmount,
        int $oldInstallments,
    ): void {
        $amountChanged = round((float) $credit->installment_amount, 2)
            !== round($oldInstallmentAmount, 2);

        $installmentsChanged = (int) $credit->installments
            !== $oldInstallments;

        $this->syncInstallments(
            $credit,
            $amountChanged,
            $installmentsChanged,
        );

        $this->recalculatePendingBalance($credit);
    }

    private function syncInstallments(
        Credit $credit,
        bool $amountChanged,
        bool $installmentsChanged,
    ): void {
        $newAmount = round((float) $credit->installment_amount, 2);
        $newCount = (int) $credit->installments;

        $last = $credit->installments()
            ->orderByDesc('number')
            ->first();

        if (! $last) {
            app(InstallmentGeneratorService::class)->generate($credit);

            return;
        }

        if ($newCount < (int) $last->number) {
            throw new InvalidArgumentException(
                'No se puede reducir el número de cuotas de un crédito ya generado.'
            );
        }

        // 1.1 Actualizar únicamente las cuotas pending y late.
        // Se conserva el importe que ya se abonó a cada cuota.
        if ($amountChanged) {
            $credit->installments()
                ->whereIn('status', self::EDITABLE_STATUSES)
                ->get()
                ->each(function (Installment $installment) use ($newAmount) {
                    $currentAmount = round(
                        (float) $installment->amount,
                        2
                    );

                    if ($currentAmount === $newAmount) {
                        return;
                    }

                    $alreadyPaid = max(
                        round(
                            $currentAmount
                            - (float) $installment->remaining_balance,
                            2
                        ),
                        0
                    );

                    $installment->update([
                        'amount' => $newAmount,
                        'remaining_balance' => max(
                            round($newAmount - $alreadyPaid, 2),
                            0
                        ),
                    ]);
                });
        }

        // 1.2 Crear las cuotas faltantes.
        // Si cambió la cantidad, el número nuevo se compara con
        // la última cuota existente, no con el total de filas.
        if ($installmentsChanged && $newCount > (int) $last->number) {
            $dueDate = $last->due_date->copy();

            for (
                $number = (int) $last->number + 1;
                $number <= $newCount;
                $number++
            ) {
                $dueDate = match ($credit->periodicity) {
                    'weekly' => $dueDate->copy()->addWeek(),
                    'monthly' => $dueDate->copy()->addMonth(),
                    'yearly' => $dueDate->copy()->addYear(),
                    default => throw new InvalidArgumentException(
                        'La periodicidad del crédito no es válida.'
                    ),
                };

                Installment::create([
                    'credit_id' => $credit->id,
                    'number' => $number,
                    'amount' => $newAmount,
                    'remaining_balance' => $newAmount,
                    'due_date' => $dueDate->copy(),
                    'status' => 'pending',
                ]);
            }
        }
    }

    private function recalculatePendingBalance(Credit $credit): void
    {
        $pendingBalance = round(
            (float) $credit->installments()->sum('remaining_balance'),
            2
        );

        $credit->update([
            'pending_balance' => $pendingBalance,
        ]);
    }
}
