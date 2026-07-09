<?php

namespace App\Console\Commands\Alerts;

use App\Services\Alerts\AlertService;
use Illuminate\Console\Command;

class CheckAlertsCommand extends Command
{
    protected $signature = 'alerts:check';

    protected $description = 'Envía las alertas pendientes';

    public function handle(): int
    {
        $alertService = app(AlertService::class);

        $alerts = $alertService->dueToday();

        if ($alerts->isEmpty()) {
            $this->info('No hay alertas pendientes.');

            return self::SUCCESS;
        }

        foreach ($alerts as $alert) {
            $alertService->notify($alert);

            $alertService->markAsSent($alert);
        }

        $this->info("Se enviaron {$alerts->count()} alertas.");

        return self::SUCCESS;
    }
}