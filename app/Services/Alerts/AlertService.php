<?php

namespace App\Services\Alerts;

use App\Models\Alert;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Installment;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\AlertDatabaseNotification;

class AlertService
{
    /**
    * Crea una alerta.
    *
    * @param array{
    *     title: string,
    *     message: string,
    *     type: string,
    *     alert_at: \Carbon\Carbon,
    *     metadata?: array|null
    * } $data
    */
    public function create(
        Client $client,
        Credit $credit,
        User $creator,
        ?User $assignedUser,
        ?Installment $installment,
        array $data,
    ): Alert {

        $alert = Alert::create([
            'client_id' => $client->id,
            'credit_id' => $credit->id,
            'installment_id' => $installment?->id,

            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'alert_at' => $data['alert_at'],

            'user_id' => $creator->id,
            'assigned_user_id' => $assignedUser?->id,

            'status' => Alert::STATUS_PENDING,

            'metadata' => $data['metadata'] ?? null,
        ]);

        return $alert;
    }

    /**
     * Alerta automática antes del vencimiento.
     */
/*     public function createUpcoming(
        Installment $installment,
        User $creator,
        ?User $assignedUser,
        int $daysBefore = 5,
    ): Alert {

        $alert = Alert::create([
            'installment_id' => $installment->id,
            'type'           => Alert::TYPE_UPCOMING_PAYMENT,
            'client_id'      => $installment->credit->client_id,
            'credit_id'      => $installment->credit_id,
            'user_id' => $creator->id,
            'assigned_user_id' => $assignedUser?->id,
            'title'          => 'Próximo pago',
            'message'        => sprintf(
                '%s debe pagar la cuota #%d el %s.',
                $installment->credit->client->full_name,
                $installment->number,
                $installment->due_date->format('d/m/Y'),
            ),
            'status'         => Alert::STATUS_PENDING,
            'alert_at'       => $installment->due_date->copy()->subDays($daysBefore),
            'metadata'       => [
                'days_before' => $daysBefore,
            ],
        ]);

        $this->notify($alert);

        return $alert;
    } */

    /**
     * Alertas que deben mostrarse hoy.
     */
    public function dueToday(): Collection
    {
        return Alert::query()
            ->where('status', Alert::STATUS_PENDING)
            ->where('alert_at', '<=', now())
            ->get();
    }

    /**
     * Marca la alerta como enviada.
     */
    public function markAsSent(Alert $alert): void
    {
        $alert->update([
            'status' => Alert::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Marca la alerta como completada.
     */
    public function complete(
        Installment $installment
    ): void {

        Alert::query()

            ->where('installment_id', $installment->id)

            ->where('status', Alert::STATUS_PENDING)

            ->update([

                'status' => Alert::STATUS_COMPLETED,
            ]);
    }

    /**
     * Cancela la alerta.
     */
    public function cancel(
        Installment $installment
    ): void {

        Alert::query()

            ->where('installment_id', $installment->id)

            ->where('status', Alert::STATUS_PENDING)

            ->update([

                'status' => Alert::STATUS_CANCELLED,
            ]);
    }

    public function updateUpcomingDate(
    Installment $installment,
    int $daysBefore = 5,
): void {

    Alert::query()

        ->where('installment_id', $installment->id)

        ->where('type', Alert::TYPE_UPCOMING_PAYMENT)

        ->update([

            'alert_at' => $installment
                ->due_date
                ->copy()
                ->subDays($daysBefore),

            'message' => sprintf(
                '%s debe pagar la cuota #%d el %s.',
                $installment->credit->client->full_name,
                $installment->number,
                $installment->due_date->format('d/m/Y'),
            ),

            'metadata' => [
                'days_before' => $daysBefore,
            ],
        ]);
}

    public function notify(Alert $alert): void
    {
        $users = collect([
            $alert->creator,
            $alert->assignedUser,
        ])
        ->filter()
        ->unique('id');

        foreach($users as $user){
            $user->notify(
                new AlertDatabaseNotification($alert)
            );
        }
    }
}