<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Installment;
use App\Models\PaymentHistory;
use App\Services\Credits\CloseCreditService;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessRuleException;

class RegisterPaymentService
{
    public function execute(
        Credit $credit,
        float $amount,
        string $paymentMethod,
        ?string $receiptNumber,
        ?string $notes = null,
        string $mode = 'auto',
        ?int $installmentId = null,
        ?int $bankId = null,
        ?string $paymentDate = null,
        ?int $userId = null,
    ): PaymentHistory {

        return DB::transaction(function () use (
            $credit,
            $amount,
            $paymentMethod,
            $receiptNumber,
            $notes,
            $mode,
            $installmentId,
            $bankId,
            $paymentDate,
            $userId,
        ) {

            $credit = Credit::whereKey($credit->id)->lockForUpdate()->firstOrFail();

            $amount = round($amount, 2);

            // El error dice qué pasa y, cuando corresponde, a qué campo pertenece: la
            // pantalla lo marca en rojo en vez de mostrar un aviso suelto.
            if ($credit->status !== 'active') {
                throw BusinessRuleException::document(__('ui.validation.credit_not_active'));
            }

            if ($amount <= 0) {
                throw BusinessRuleException::field('amount', __('ui.validation.payment_amount_positive'));
            }

            // Lo máximo que este pago puede cubrir
            $unpaid = $credit->installments()->whereIn('status', Installment::UNPAID_STATUSES);

            if ($mode === 'single') {
                $from = Installment::where('credit_id', $credit->id)
                    ->findOrFail($installmentId)
                    ->number;

                $unpaid->where('number', '>=', $from);
            }

            $maxPayable = round((float) $unpaid->sum('remaining_balance'), 2);

            if ($amount > $maxPayable) {
                throw BusinessRuleException::field('amount', __('ui.validation.payment_over_balance', [
                    'amount' => number_format($amount, 2),
                    'pending' => number_format($maxPayable, 2),
                ]));
            }

            $previousBalance = $credit->pending_balance;

            $paymentHistory = PaymentHistory::create([
                'credit_id'        => $credit->id,
                'user_id'          => $userId ?? auth()->id(),
                'amount'           => $amount,
                'payment_method'   => $paymentMethod,
                'payment_date'     => $paymentDate ?? now(),
                'receipt_number'   => $receiptNumber,
                'previous_balance' => $previousBalance,
                'new_balance'      => $previousBalance,
                'notes'            => $notes,
                'bank_id'          => $bankId,
            ]);

            app(ApplyPaymentService::class)->apply(
                $credit,
                $amount,
                $mode,
                $installmentId,
                $paymentDate,
            );

            app(CloseCreditService::class)->execute($credit);

            $paymentHistory->update([
                'new_balance' => $credit->fresh()->pending_balance,
            ]);

            return $paymentHistory;
        });
    }
}