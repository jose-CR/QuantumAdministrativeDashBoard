<?php

namespace App\Console\Commands\Alerts;

use App\Models\Alert;
use App\Models\Installment;
use App\Services\Alerts\AlertService;
use Illuminate\Console\Command;

/**
 * Crea las alertas que faltan: una por cuota vencida y una por cuota que vence
 * dentro de los próximos días. Es idempotente: si la alerta ya existe (aunque
 * ya se haya enviado), no la duplica.
 */
class GenerateAlertsCommand extends Command
{
    protected $signature = 'alerts:generate {--days=5 : Días de anticipación del aviso}';

    protected $description = 'Crea las alertas de cuotas vencidas y próximas a vencer';

    public function handle(AlertService $alerts): int
    {
        $days = max(0, (int) $this->option('days'));

        $installments = Installment::query()
            ->whereIn('status', Installment::UNPAID_STATUSES)
            ->whereDate('due_date', '<=', today()->addDays($days))
            ->whereHas('credit', fn ($query) => $query->where('status', 'active'))
            ->with('credit.client')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($installments as $installment) {
            $overdue = $installment->due_date->lt(today());

            $type = $overdue
                ? Alert::TYPE_OVERDUE
                : Alert::TYPE_UPCOMING_PAYMENT;

            if ($alerts->existsFor($installment, $type)) {
                $skipped++;

                continue;
            }

            $overdue
                ? $alerts->createOverdue($installment)
                : $alerts->createUpcoming($installment, daysBefore: $days);

            $created++;
        }

        $this->info("Alertas nuevas: {$created}. Ya existían: {$skipped}.");

        return self::SUCCESS;
    }
}
