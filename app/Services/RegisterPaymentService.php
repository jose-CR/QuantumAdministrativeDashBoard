<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Installment;
use App\Models\PaymentHistory;
use App\Services\Credits\CloseCreditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
        ) {

            $credit = Credit::whereKey($credit->id)->lockForUpdate()->firstOrFail();

            $amount = round($amount, 2);

            if ($credit->status !== 'active') {
                throw new InvalidArgumentException('Solo se pueden registrar pagos en créditos activos.');
            }

            if ($amount <= 0) {
                throw new InvalidArgumentException('El monto del pago debe ser mayor que cero.');
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
                throw new InvalidArgumentException(
                    "El pago ({$amount}) supera lo pendiente ({$maxPayable})."
                );
            }

            $previousBalance = $credit->pending_balance;

            $paymentHistory = PaymentHistory::create([
                'credit_id'        => $credit->id,
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