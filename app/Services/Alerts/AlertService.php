<?php

namespace App\Services\Alerts;

use App\Models\Alert;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\User;
use App\Notifications\AlertDatabaseNotification;
use Illuminate\Database\Eloquent\Collection;

class AlertService
{
    /**
     * Crea una alerta.
     *
     * @param  array{title: string, message: string, type: string, alert_at: \Carbon\Carbon, metadata?: array|null}  $data
     */
    public function create(
        Customer $customer,
        Credit $credit,
        ?User $creator,
        ?User $assignedUser,
        ?Installment $installment,
        array $data,
    ): Alert {
        return Alert::create([
            'customer_id' => $customer->id,
            'credit_id' => $credit->id,
            'installment_id' => $installment?->id,

            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'alert_at' => $data['alert_at'],

            'user_id' => $creator?->id,
            'assigned_user_id' => $assignedUser?->id,

            'status' => Alert::STATUS_PENDING,

            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Alerta automática antes del vencimiento de una cuota.
     */
    public function createUpcoming(
        Installment $installment,
        ?User $creator = null,
        ?User $assignedUser = null,
        int $daysBefore = 5,
    ): Alert {
        $credit = $installment->credit;

        return $this->create(
            customer: $credit->client,
            credit: $credit,
            creator: $creator,
            assignedUser: $assignedUser,
            installment: $installment,
            data: [
                'type' => Alert::TYPE_UPCOMING_PAYMENT,
                'title' => __('resources.alert.upcoming_payment'),
                'message' => sprintf(
                    __('resources.alert.upcoming_message'),
                    $credit->client->full_name,
                    $installment->number,
                    $installment->due_date->format('d/m/Y'),
                ),
                'alert_at' => $installment->due_date->copy()->subDays($daysBefore),
                'metadata' => ['days_before' => $daysBefore],
            ],
        );
    }

    /**
     * Alerta de cuota vencida: se muestra de inmediato.
     */
    public function createOverdue(
        Installment $installment,
        ?User $creator = null,
        ?User $assignedUser = null,
    ): Alert {
        $credit = $installment->credit;

        return $this->create(
            customer: $credit->client,
            credit: $credit,
            creator: $creator,
            assignedUser: $assignedUser,
            installment: $installment,
            data: [
                'type' => Alert::TYPE_OVERDUE,
                'title' => __('resources.alert.overdue_payment'),
                'message' => sprintf(
                    __('resources.alert.overdue_message'),
                    $credit->client->full_name,
                    $installment->number,
                    $installment->due_date->format('d/m/Y'),
                    number_format((float) $installment->remaining_balance, 2),
                ),
                'alert_at' => now(),
            ],
        );
    }

    /**
     * ¿Ya existe una alerta de este tipo para la cuota? Evita duplicados en cada
     * corrida del generador (aunque la anterior ya se haya enviado).
     */
    public function existsFor(Installment $installment, string $type): bool
    {
        return Alert::query()
            ->where('installment_id', $installment->id)
            ->where('type', $type)
            ->exists();
    }

    /**
     * Alertas que deben mostrarse ya (vencidas o en su fecha).
     */
    public function dueToday(): Collection
    {
        return Alert::query()
            ->where('status', Alert::STATUS_PENDING)
            ->where('alert_at', '<=', now())
            ->get();
    }

    public function markAsSent(Alert $alert): void
    {
        $alert->update([
            'status' => Alert::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * La cuota se pagó: sus alertas pendientes ya no aplican.
     */
    public function complete(Installment $installment): void
    {
        Alert::query()
            ->where('installment_id', $installment->id)
            ->where('status', Alert::STATUS_PENDING)
            ->update(['status' => Alert::STATUS_COMPLETED]);
    }

    public function cancel(Installment $installment): void
    {
        Alert::query()
            ->where('installment_id', $installment->id)
            ->where('status', Alert::STATUS_PENDING)
            ->update(['status' => Alert::STATUS_CANCELLED]);
    }

    public function updateUpcomingDate(Installment $installment, int $daysBefore = 5): void
    {
        Alert::query()
            ->where('installment_id', $installment->id)
            ->where('type', Alert::TYPE_UPCOMING_PAYMENT)
            ->update([
                'alert_at' => $installment->due_date->copy()->subDays($daysBefore),
                'message' => sprintf(
                    __('resources.alert.upcoming_message'),
                    $installment->credit->client->full_name,
                    $installment->number,
                    $installment->due_date->format('d/m/Y'),
                ),
                'metadata' => ['days_before' => $daysBefore],
            ]);
    }

    /**
     * A quién le llega la alerta: el asignado y quien la creó; si no hay
     * ninguno (alertas automáticas), a quienes trabajan con créditos.
     */
    public function recipientsFor(Alert $alert): Collection
    {
        $explicit = collect([$alert->creator, $alert->assignedUser])
            ->filter()
            ->unique('id')
            ->values();

        if ($explicit->isNotEmpty()) {
            return new Collection($explicit->all());
        }

        $recipients = User::query()
            ->permission(['ViewAny:Credit', 'ViewAny:Installment'])
            ->get();

        if ($recipients->isEmpty()) {
            $recipients = User::query()->role('super_admin')->get();
        }

        return $recipients;
    }

    /**
     * Manda la alerta al panel (campana de notificaciones).
     */
    public function notify(Alert $alert): void
    {
        foreach ($this->recipientsFor($alert) as $user) {
            $user->notify(new AlertDatabaseNotification($alert));
        }
    }
}
