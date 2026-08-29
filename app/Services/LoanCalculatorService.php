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

        $downPayment = round((float) ($data['down_payment'] ?? 0), 2);

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

        $financedAmount = round($initialAmount - $downPayment, 2);

        $installments = (int) ($data['installments'] ?? 0);
        $installmentAmount = round((float) ($data['installment_amount'] ?? 0), 2);

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

        $totalAmount = round($installments * $installmentAmount, 2);

        // H12 del motor financiero: (cuota * plazo) - financiado.
        // Si es <= 0, el motor lo trata como crédito sin interés (0%),
        // no como error: el saldo se sigue abonando con pagos reales
        // hasta llegar a 0, aunque tome más cuotas que "installments".
        $totalInterest = max(0, round($totalAmount - $financedAmount, 2));

        // Tasa MENSUAL, igual que tasaMensual() del motor JS:
        // (interesesTotales / plazo) / financiado. Ojo: esto ya NO es
        // la tasa total del crédito, es la tasa por período.
        $interestRate = ($financedAmount > 0 && $totalInterest > 0)
            ? round((($totalInterest / $installments) / $financedAmount) * 100, 2)
            : 0;

        return [
            'initial_amount'  => $initialAmount,
            'down_payment'    => $downPayment,
            'financed_amount' => $financedAmount,
            'total_amount'    => $totalAmount,
            'total_interest'  => $totalInterest,
            'interest_rate'   => $interestRate,
            // Saldo a capital real (lo que consume el motor de amortización),
            // NO el total de cuotas cotizadas.
            'pending_balance' => $financedAmount,
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
            return round((float) ($data['initial_amount'] ?? 0), 2);
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