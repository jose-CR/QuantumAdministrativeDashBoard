<?php

namespace App\Services;

use InvalidArgumentException;

class LoanCalculatorService
{
    public function calculate(array $data): array
    {
        $initialAmount = $this->resolveInitialAmount($data);

        if ($initialAmount <= 0) {
            throw new InvalidArgumentException(
                'El monto inicial debe ser mayor que cero.'
            );
        }

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

        /*
         * Total cotizado por las cuotas.
         */
        $totalAmount = round(
            $installments * $installmentAmount,
            2
        );

        /*
         * Intereses totales.
         *
         * Si el total de cuotas no supera el monto financiado,
         * el crédito se considera sin intereses.
         */
        $totalInterest = max(
            0,
            round(
                $totalAmount - $financedAmount,
                2
            )
        );

        /*
         * Tasa por período.
         */
        $interestRate = (
            $financedAmount > 0 &&
            $totalInterest > 0
        )
            ? round(
                (
                    ($totalInterest / $installments)
                    / $financedAmount
                ) * 100,
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
            'pending_balance' => $financedAmount,
        ];
    }

    /**
     * Suma el precio de todos los items del crédito.
     *
     * Cada item puede ser un vehículo o un transporte,
     * pero el cálculo financiero solamente necesita su precio.
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
            collect($data['items'])
                ->sum(
                    fn ($item) => (float) (
                        $item['price'] ?? 0
                    )
                ),
            2
        );
    }
}