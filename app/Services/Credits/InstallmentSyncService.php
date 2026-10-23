<?php

namespace App\Services\Credits;

use App\Models\Credit;
use App\Models\Installment;
use App\Services\InstallmentGeneratorService;
use InvalidArgumentException;

class InstallmentSyncService
{
    private const EDITABLE = ['pending', 'late', 'partial'];

    public function sync(Credit $credit): void
    {
        $newAmount = round((float) $credit->installment_amount, 2);
        $count     = (int) $credit->installments;
        $financed  = (float) $credit->financed_amount;

        $last = $credit->installments()->orderByDesc('number')->first();

        if (! $last) {
            app(InstallmentGeneratorService
            ::class)->generate($credit);
            $this->refreshTotals($credit);
            return;
        }

        if ($count < $last->number) {
            throw new InvalidArgumentException(
                'No se puede reducir el número de cuotas de un crédito ya generado.'
            );
        }

        // 1. Cuotas faltantes
        $dueDate = $last->due_date->copy();

        for ($n = $last->number + 1; $n <= $count; $n++) {
            $dueDate = match ($credit->periodicity) {
                'weekly'  => $dueDate->copy()->addWeek(),
                'monthly' => $dueDate->copy()->addMonth(),
                'yearly'  => $dueDate->copy()->addYear(),
            };

            Installment::create([
                'credit_id'         => $credit->id,
                'number'            => $n,
                'amount'            => $newAmount,
                'remaining_balance' => $newAmount,
                'due_date'          => $dueDate->copy(),
                'status'            => 'pending',
            ]);
        }

        // 2. Monto nuevo en las cuotas no pagadas anteriores a la última
        $credit->installments()
            ->whereIn('status', self::EDITABLE)
            ->where('number', '<', $count)
            ->where('amount', '!=', $newAmount)
            ->get()
            ->each(fn (Installment $i) => $this->setAmount($i, $newAmount));

        // 3. La última absorbe la diferencia si las cuotas no cubren lo financiado
        $others     = (float) $credit->installments()->where('number', '<', $count)->sum('amount');
        $lastAmount = round(max($newAmount, $financed - $others), 2);
        $final      = $credit->installments()->where('number', $count)->first();

        if ($final
            && in_array($final->status, self::EDITABLE)
            && abs((float) $final->amount - $lastAmount) > 0.004) {
            $this->setAmount($final, $lastAmount);
        }

        // 4. Recalcular el crédito desde las cuotas reales
        $this->refreshTotals($credit);
    }

    private function setAmount(Installment $installment, float $amount): void
    {
        $paid = (float) $installment->amount - (float) $installment->remaining_balance;

        $installment->update([
            'amount'            => $amount,
            'remaining_balance' => max(round($amount - $paid, 2), 0),
        ]);
    }

    private function refreshTotals(Credit $credit): void
    {
        $total    = round((float) $credit->installments()->sum('amount'), 2);
        $financed = (float) $credit->financed_amount;
        $count    = (int) $credit->installments;
        $interest = max(0, round($total - $financed, 2));

        $credit->update([
            'total_amount'    => $total,
            'total_interest'  => $interest,
            'interest_rate'   => ($financed > 0 && $interest > 0 && $count > 0)
                ? round((($interest / $count) / $financed) * 100, 2)
                : 0,
            'pending_balance' => round((float) $credit->installments()->sum('remaining_balance'), 2),
        ]);
    }
}