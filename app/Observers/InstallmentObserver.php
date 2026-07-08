<?php

namespace App\Observers;

use App\Models\Installment;
use App\Services\Alerts\AlertService;


class InstallmentObserver
{
    /**
     * Se crea una nueva cuota.
     */
    public function created(Installment $installment): void
    {
        app(AlertService::class)
            ->createUpcoming($installment);
    }

    /**
     * Se actualiza una cuota.
     */
    public function updated(Installment $installment): void
    {
        $alertService = app(AlertService::class);

        /*
        |--------------------------------------------------------------
        | La cuota fue pagada
        |--------------------------------------------------------------
        */
        if (
            $installment->wasChanged('status')
            && $installment->status === 'paid'
        ) {
            $alertService->complete($installment);

            return;
        }

        /*
        |--------------------------------------------------------------
        | La cuota fue refinanciada o cancelada
        |--------------------------------------------------------------
        */
        if (
            $installment->wasChanged('status')
            && in_array($installment->status, [
                'refinanced',
                'cancelled',
            ])
        ) {
            $alertService->cancel($installment);

            return;
        }

        /*
        |--------------------------------------------------------------
        | Cambió la fecha de vencimiento
        |--------------------------------------------------------------
        */
        if ($installment->wasChanged('due_date')) {

            $alertService->updateUpcomingDate($installment);
        }
    }

    /**
     * Se elimina una cuota.
     */
    public function deleted(Installment $installment): void
    {
        app(AlertService::class)
            ->cancel($installment);
    }
}
