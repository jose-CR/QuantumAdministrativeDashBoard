<?php

namespace App\Support;

/**
 * Estado de cobro de una entrada de caja.
 *
 * El estado se deriva del monto cobrado (`amount_paid`), no se elige a mano:
 * antes se podía marcar "parcial" sin decir cuánto se cobró y ningún reporte
 * podía calcular el saldo.
 */
class PaymentStatus
{
    public static function label(?string $state): string
    {
        return match ($state) {
            'pending' => __('resources.clients.statuses.pending'),
            'partial' => __('resources.clients.statuses.partial'),
            'paid' => __('resources.clients.statuses.paid'),
            default => (string) $state,
        };
    }

    public static function color(?string $state): string
    {
        return match ($state) {
            'pending' => 'danger',
            'partial' => 'warning',
            'paid' => 'success',
            default => 'gray',
        };
    }

    /**
     * Métodos de pago que acepta la base (CHECK de inflows.payment_method).
     *
     * @return array<string, string>
     */
    public static function methods(): array
    {
        return collect(['cash', 'card', 'transfer', 'cheque', 'quedan'])
            ->mapWithKeys(fn (string $metodo): array => [
                $metodo => __('resources.credits.clients.pay_installment.payment_methods.' . $metodo),
            ])
            ->all();
    }

    public static function methodLabel(?string $state): string
    {
        return self::methods()[$state] ?? (string) $state;
    }
}
