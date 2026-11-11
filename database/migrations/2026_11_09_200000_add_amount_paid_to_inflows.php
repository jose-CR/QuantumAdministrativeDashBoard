<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cuánto se cobró de cada entrada.
 *
 * El estado (`payment_status`) era un select suelto: se podía marcar "parcial"
 * sin decir cuánto entró, así que ni la pantalla ni el reporte de caja podían
 * calcular el saldo por cobrar. Ahora el estado se deriva de este monto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inflows', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)
                ->default(0)
                ->after('amount');
        });

        // Lo que ya decía "pagado" se cobró entero. Los parciales quedan en 0:
        // el monto real de esos cobros no está registrado en ningún lado y no se
        // puede inventar.
        DB::table('inflows')
            ->where('payment_status', 'paid')
            ->update(['amount_paid' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('inflows', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
