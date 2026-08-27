<?php

namespace App\Services;

use InvalidArgumentException;

class LoanCalculatorService
{
    public function calculate(array $data): array
    {
        $initialAmount = $this->resolveInitialAmount($data);

        $downPayment = round(
            (float) ($data['down_payment'] ?? 0),
            2
        );

        if ($downPayment < 0) {
            throw new InvalidArgumentException(
                'El pago inicial no puede ser negativo.'
            );
        }

        if ($downPayment > $initialAmount) {
            throw new InvalidArgumentException(
                'El pago inicial no puede ser mayor al monto inicial.'
            );
        }

        $financedAmount = round(
            $initialAmount - $downPayment,
            2
        );

        $installments = (int) ($data['installments'] ?? 0);

        $installmentAmount = round(
            (float) ($data['installment_amount'] ?? 0),
            2
        );

        if ($installments <= 0) {
            throw new InvalidArgumentException(
                'El número de cuotas debe ser mayor que cero.'
            );
        }

        if ($installmentAmount <= 0) {
            throw new InvalidArgumentException(
                'El monto de la cuota debe ser mayor que cero.'
            );
        }

        $totalAmount = round(
            $installments * $installmentAmount,
            2
        );

        if ($totalAmount < $financedAmount) {
            throw new InvalidArgumentException(
                'El total de las cuotas no puede ser menor al monto financiado.'
            );
        }

        $totalInterest = round(
            $totalAmount - $financedAmount,
            2
        );

        $interestRate = $financedAmount > 0
            ? round(
                ($totalInterest / $financedAmount) * 100,
                2
            )
            : 0;

        return [
            'initial_amount'  => $initialAmount,
            'down_payment'    => $downPayment,
            'financed_amount' => $financedAmount,
            'total_amount'    => $totalAmount,
            'total_interest'  => $totalInterest,
            'interest_rate'   => $interestRate,
            'pending_balance' => $totalAmount,
        ];
    }

    /**
     * Suma el precio de los items del crédito.
     *
     * Si no vienen items, utiliza initial_amount.
     */
    protected function resolveInitialAmount(array $data): float
    {
        if (empty($data['items'])) {
            return round(
                (float) ($data['initial_amount'] ?? 0),
                2
            );
        }

        return round(
            collect($data['items'])->sum(
                fn ($item) => (float) (
                    is_array($item)
                        ? ($item['price'] ?? 0)
                        : ($item->price ?? 0)
                )
            ),
            2
        );
    }
}