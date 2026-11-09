<?php

namespace App\Services\Alerts;

use App\Models\Alert;

/**
 * Se conserva por compatibilidad: el envío vive en AlertService::notify(), que
 * resuelve los destinatarios (asignado, creador o quienes ven créditos).
 *
 * La versión anterior llamaba `$user()->auth()->user()`, que no es PHP válido y
 * reventaba en cuanto se usaba.
 */
class AlertNotificationService
{
    public function __construct(
        private readonly AlertService $alerts,
    ) {}

    public function send(Alert $alert): void
    {
        $this->alerts->notify($alert);
    }
}
