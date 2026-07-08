<?php

namespace App\Notifications;

use App\Models\Alert;
use Filament\Notifications\Notification as NotificationsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertDatabaseNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private Alert $alert)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->alert->title,
            'body' => $this->alert->message,
            'alert_id' => $this->alert->id,
            'type' => $this->alert->type,
            'client_id' => $this->alert->client_id,
            'credit_id' => $this->alert->credit_id,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationsNotification::make()
            ->title($this->alert->title)
            ->body($this->alert->message)
            ->icon('heroicon-o-bell')
            ->color('warning')
            ->getDatabaseMessage();
    }
}
