<?php

use App\Console\Commands\Alerts\CheckAlertsCommand;
use App\Console\Commands\Alerts\GenerateAlertsCommand;
use App\Console\Commands\Credits\MarkLateInstallmentsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Marca las cuotas vencidas como atrasadas: sin esto el estado 'late' no se
// escribe nunca y el panel las muestra al día. Corre antes de generar alertas.
Schedule::command(MarkLateInstallmentsCommand::class)
    ->dailyAt('06:45')
    ->withoutOverlapping();

// Crea las alertas que faltan (cuotas vencidas y por vencer) una vez al día.
Schedule::command(GenerateAlertsCommand::class)
    ->dailyAt('07:00')
    ->withoutOverlapping();

// Envía al panel las que ya tocan. Sin este comando, la campana queda vacía.
Schedule::command(CheckAlertsCommand::class)
    ->everyMinute()
    ->withoutOverlapping();
