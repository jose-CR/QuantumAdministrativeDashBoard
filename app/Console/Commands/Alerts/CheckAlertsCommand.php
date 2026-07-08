<?php

namespace App\Console\Commands\Alerts;

use App\Models\User;
use App\Services\Alerts\AlertService as AlertsAlertService;
use App\Services\AlertService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckAlertsCommand extends Command
{
    protected $signature = 'alerts:check';

    protected $description = 'Envía las alertas pendientes';

    public function handle(): int
    {
        $alerts = app(AlertsAlertService::class)
            ->dueToday();

        if ($alerts->isEmpty()) {
            return self::SUCCESS;
        }
        
        foreach ($alerts as $alert) {

            if ($alert->assignedUser) {
                Notification::make()
                    ->title($alert->title)
                    ->body($alert->message)
                    ->warning()
                    ->sendToDatabase($alert->assignedUser);
            }

            app(AlertsAlertService::class)
                ->markAsSent($alert);
        }

        return self::SUCCESS;
    }
}