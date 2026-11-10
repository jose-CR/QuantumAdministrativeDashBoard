<?php

namespace App\Support;

/**
 * Etiqueta y color de los estados de un crédito.
 *
 * Estaba copiado a mano en cada tabla, y en la ficha del cliente se comparaba el
 * valor crudo contra la etiqueta traducida: nunca coincidía y el badge salía
 * siempre gris.
 */
class CreditStatus
{
    public static function label(?string $state): string
    {
        return match ($state) {
            'active' => __('resources.statuses.credit.active'),
            'completed' => __('resources.statuses.credit.completed'),
            'refinanced' => __('resources.statuses.credit.refinanced'),
            'cancelled' => __('resources.statuses.credit.cancelled'),
            'pending' => __('resources.statuses.credit.pending'),
            'paid' => __('resources.statuses.credit.paid'),
            'defaulted' => __('resources.statuses.credit.defaulted'),
            default => (string) $state,
        };
    }

    public static function color(?string $state): string
    {
        return match ($state) {
            'active' => 'success',
            'completed' => 'info',
            'refinanced' => 'warning',
            'cancelled' => 'danger',
            'pending' => 'warning',
            'paid' => 'info',
            'defaulted' => 'danger',
            default => 'gray',
        };
    }
}
