<?php

namespace App\Services\Alerts;

use App\Models\Alert;
use Filament\Notifications\Notification;

class AlertNotificationService
{
    /**
     * Create a new class instance.
     */
    public function send(Alert $alert): void
    {
        $users = collect([
            $alert->creator,
            $alert->assignedUser,
        ])
        ->filter()
        ->unique('id');

        foreach($users as $user){
            $recipient = $user()->auth()->user();

            Notification::make()
                ->title($alert->title)
                ->body($alert->message)
                ->broadcast($recipient)
                ->sendToDatabase($user);
        }
    }
}
