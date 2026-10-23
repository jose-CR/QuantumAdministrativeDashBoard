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

        $maxInstallments = (int) config('quantum.installments.max');

        if ($installments > $maxInstallments) {
            throw new InvalidArgumentException(
                "El número de cuotas no puede ser mayor a {$maxInstallments}."
            );
        }
        
        /*
        * Total cotizado por las cuotas.
        */
        $quotedTotal = round($installments * $installmentAmount, 2);

        // Si las cuotas no cubren lo financiado, la última cuota absorbe la diferencia
        $totalAmount = max($quotedTotal, $financedAmount);

        // Tope de razón: evita errores de captura (cuota con un cero de más)
        $maxRatio = (float) config('quantum.installments.max_total_ratio', 3);

        if ($financedAmount > 0 && $totalAmount > $financedAmount * $maxRatio) {
            throw new InvalidArgumentException(
                "El total de las cuotas ({$totalAmount}) supera {$maxRatio} veces lo financiado. Revisa el monto de la cuota."
            );
        }

        /*
        * Intereses totales.

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
        if (! empty($data['refinanced_from_id'])) {
            return round((float) ($data['initial_amount'] ?? 0), 2);
        }

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