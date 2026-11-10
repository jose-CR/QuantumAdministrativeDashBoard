<?php

namespace App\Console\Commands\Credits;

use App\Models\Installment;
use Illuminate\Console\Command;

/**
 * Marca como atrasadas las cuotas vencidas que siguen pendientes.
 *
 * Sin esto el estado 'late' nunca se escribe: una cuota vencida se quedaba en
 * 'pending' y el panel la mostraba como si estuviera al día, aunque el saldo
 * vencido se calculara por fecha.
 */
class MarkLateInstallmentsCommand extends Command
{
    protected $signature = 'credits:mark-late';

    protected $description = 'Marca como atrasadas las cuotas vencidas que todavía están pendientes';

    public function handle(): int
    {
        $marcadas = Installment::query()
            ->where('status', 'pending')
            ->where('remaining_balance', '>', 0)
            ->whereDate('due_date', '<', today())
            ->whereHas('credit', fn ($query) => $query->where('status', 'active'))
            ->update([
                'status' => 'late',
                'updated_at' => now(),
            ]);

        $this->info("Cuotas marcadas como atrasadas: {$marcadas}");

        return self::SUCCESS;
    }
}
