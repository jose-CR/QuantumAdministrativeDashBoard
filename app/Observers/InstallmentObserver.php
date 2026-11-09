<?php

namespace App\Observers;

use App\Models\Installment;
use App\Services\Alerts\AlertService;

class InstallmentObserver
{
    public function __construct(
        private readonly AlertService $alerts,
    ) {}

    /**
     * Cuando una cuota queda pagada, sus alertas pendientes dejan de tener
     * sentido: se marcan como completadas (no se borran, quedan en el historial).
     */
    public function updated(Installment $installment): void
    {
        if ($installment->wasChanged('status') && $installment->status === 'paid') {
            $this->alerts->complete($installment);
        }
    }
}
