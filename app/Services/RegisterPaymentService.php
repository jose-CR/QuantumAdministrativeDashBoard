<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\PaymentHistory;
use App\Services\Credits\CloseCreditService;
use Illuminate\Support\Facades\DB;

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

            app(
                ApplyPaymentService::class
            )->apply(
                $credit,
                $amount,
                $mode,
                $installmentId,
                $paymentDate,
            );

            app(CloseCreditService::class)->execute($credit);

            $paymentHistory->update([
                'new_balance' => $credit
                    ->fresh()
                    ->pending_balance,
            ]);

            return $paymentHistory;
        });
    }
}