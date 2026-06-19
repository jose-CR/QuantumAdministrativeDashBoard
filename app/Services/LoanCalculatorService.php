<?php

namespace App\Services;

class LoanCalculatorService
{
    /**
     * Create a new class instance.
     */
    public function calculate(array $data): array
    {
        $financedAmount = $data['initial_amount']
            - $data['down_payment'];

        $totalAmount = $data['installments']
            * $data['installment_amount'];

        $totalInterest = $totalAmount
            - $financedAmount;

        $interestRate = $financedAmount > 0
            ? ($totalInterest / $financedAmount) * 100
            : 0;

        return [
            'financed_amount' => round($financedAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'interest_rate' => round($interestRate, 2),
            'pending_balance' => round($totalAmount, 2),
        ];
    }
}
