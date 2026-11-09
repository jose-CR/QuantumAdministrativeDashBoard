<?php

namespace App\Models\Concerns;

/**
 * Etiqueta con los créditos que impiden borrar un registro, en formato
 * "#12 (Cliente), #34 (Cliente) y 3 más", para que se puedan buscar.
 *
 * El modelo que la usa tiene que implementar creditsBlockingDeletion().
 */
trait HasCreditBlockedDeletion
{
    public function creditsBlockingDeletionLabel(int $limit = 5): string
    {
        $credits = $this->creditsBlockingDeletion();

        if ($credits->isEmpty()) {
            return '';
        }

        $label = $credits->take($limit)
            ->map(fn (array $credit): string => '#' . $credit['id']
                . ($credit['customer'] !== '' ? " ({$credit['customer']})" : ''))
            ->implode(', ');

        $remaining = $credits->count() - $limit;

        return $remaining > 0
            ? $label . ' ' . __('notifications.inventary.more_credits', ['count' => $remaining])
            : $label;
    }
}
