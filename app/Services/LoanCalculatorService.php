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

        if ($installments <= 0) {
            throw new InvalidArgumentException(
                'El número de cuotas debe ser mayor que cero.'
            );
        }

        $maxInstallments = (int) config('quantum.installments.max');

        if ($installments > $maxInstallments) {
            throw new InvalidArgumentException(
                "El número de cuotas no puede ser mayor a {$maxInstallments}."
            );
        }

        $interestType = ($data['interest_type'] ?? 'auto') === 'fixed' ? 'fixed' : 'auto';

        if ($interestType === 'fixed') {
            // El interés es el que se pactó: la tasa manda y la cuota sale de ella.
            if ($financedAmount <= 0) {
                throw new InvalidArgumentException(
                    'No hay saldo que financiar: el pago inicial cubre el monto total.'
                );
            }

            $interestRate = round((float) ($data['interest_rate'] ?? 0), 2);

            $maxRate = (float) config('quantum.interest.max_rate', 20);

            if ($interestRate < 0) {
                throw new InvalidArgumentException('La tasa de interés no puede ser negativa.');
            }

            if ($interestRate > $maxRate) {
                throw new InvalidArgumentException(
                    "La tasa de interés por cuota no puede superar {$maxRate}%."
                );
            }

            $totalAmount = round(
                $financedAmount * (1 + ($interestRate / 100) * $installments),
                2
            );

            $totalInterest = round($totalAmount - $financedAmount, 2);

            // Hacia abajo: la última cuota absorbe el redondeo y nunca queda menor
            // que las demás.
            $installmentAmount = floor(($totalAmount / $installments) * 100) / 100;
        } else {
            $installmentAmount = round(
                (float) ($data['installment_amount'] ?? 0),
                2
            );

            if ($installmentAmount <= 0) {
                throw new InvalidArgumentException(
                    'El monto de la cuota debe ser mayor que cero.'
                );
            }

            /*
             * Total cotizado por las cuotas.
             */
            $quotedTotal = round($installments * $installmentAmount, 2);

            // Si las cuotas no cubren lo financiado, la última cuota absorbe la diferencia
            $totalAmount = max($quotedTotal, $financedAmount);

            /*
             * Intereses totales.
             *
             * Si el total de cuotas no supera el monto financiado,
             * el crédito se considera sin intereses.
             */
            $totalInterest = max(
                0,
                round($totalAmount - $financedAmount, 2)
            );

            /*
             * Tasa por período, la que sale de la cuota que se cobra.
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
        }

        // Tope de razón: evita errores de captura (cuota con un cero de más)
        $maxRatio = (float) config('quantum.installments.max_total_ratio', 3);

        if ($financedAmount > 0 && $totalAmount > $financedAmount * $maxRatio) {
            throw new InvalidArgumentException(
                "El total de las cuotas ({$totalAmount}) supera {$maxRatio} veces lo financiado. Revisa el monto de la cuota."
            );
        }

        return [
            'initial_amount'     => $initialAmount,
            'down_payment'       => $downPayment,
            'financed_amount'    => $financedAmount,
            'installments'       => $installments,
            'installment_amount' => $installmentAmount,
            'total_amount'       => $totalAmount,
            'total_interest'     => $totalInterest,
            'interest_type'      => $interestType,
            'interest_rate'      => $interestRate,
            'pending_balance'    => $financedAmount,
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
                    fn ($item) => (float) ($item['price'] ?? 0)
                        * max(1, (int) ($item['quantity'] ?? 1))
                ),
            2
        );
    }
}