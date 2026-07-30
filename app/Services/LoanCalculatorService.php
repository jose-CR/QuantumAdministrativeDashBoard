<?php

namespace App\Services;

class LoanCalculatorService
{
    public function calculate(array $data): array
    {
        $initialAmount = (float) $data['initial_amount'];

        $downPayment = (float) ($data['down_payment'] ?? 0);

        $financedAmount = $initialAmount - $downPayment;

        $totalAmount = (float) $data['installments']
            * (float) $data['installment_amount'];

        $totalInterest = $totalAmount - $financedAmount;

        $interestRate = $financedAmount > 0
            ? ($totalInterest / $financedAmount) * 100
            : 0;

        return [
            'down_payment' => $downPayment,
            'financed_amount' => round($financedAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'interest_rate' => round($interestRate, 2),
            'pending_balance' => round($totalAmount, 2),
        ];
    }
}
