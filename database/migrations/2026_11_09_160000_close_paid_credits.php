<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cierra los créditos que ya están pagados pero quedaron abiertos.
 *
 * Había cuotas 'pending' con saldo 0 (pagos que las cerraron por redondeo y no
 * cambiaron el estado). Como el saldo total sumaba 0, el crédito tampoco se
 * cerraba: quedaba 'active' con una cuota pendiente que ya no debía nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Una cuota sin saldo es una cuota pagada.
        $cuotas = DB::table('installments')
            ->whereIn('status', ['pending', 'late'])
            ->where('remaining_balance', '<=', 0)
            ->update([
                'status' => 'paid',
                'paid_at' => DB::raw('coalesce(paid_at, updated_at, now())'),
                'updated_at' => now(),
            ]);

        // 2. Un crédito sin cuotas impagas está completado.
        $creditos = DB::table('credits')
            ->where('status', 'active')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('installments')
                    ->whereColumn('installments.credit_id', 'credits.id')
                    ->whereIn('installments.status', ['pending', 'late']);
            })
            ->update([
                'status' => 'completed',
                'pending_balance' => 0,
                'updated_at' => now(),
            ]);

        echo "  cuotas marcadas como pagadas: {$cuotas}\n";
        echo "  creditos cerrados: {$creditos}\n";
    }

    public function down(): void
    {
        // No se revierte: marcar cuotas pagadas como pendientes otra vez volvería
        // a dejar los créditos descuadrados. El respaldo previo está en
        // storage/app/backups/creditos-antes-de-cerrar-<fecha>.sql
    }
};
