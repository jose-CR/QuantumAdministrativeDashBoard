<?php

use App\Models\Credit;
use App\Models\Installment;
use Illuminate\Database\Migrations\Migration;

/**
 * Corrige el vencimiento de las cuotas ya generadas.
 *
 * Se generaban sumando meses al día de inicio (un crédito del 10/11 vencía el 10 de
 * cada mes) en vez de usar el día de pago pactado. Acá se reescriben las cuotas
 * **impagas**; las pagadas se dejan como están, porque son historial y su fecha es la
 * que se cobró.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cambiadas = 0;
        $creditos = 0;

        Credit::query()
            ->chunkById(100, function ($pagina) use (&$cambiadas, &$creditos): void {
                foreach ($pagina as $credito) {
                    // `$credito->installments` devuelve el NÚMERO de cuotas (columna de
                    // `credits`), no la relación: hay que consultarla con paréntesis.
                    $cuotas = $credito->installments()
                        ->whereIn('status', ['pending', 'late'])
                        ->where('remaining_balance', '>', 0)
                        ->orderBy('number')
                        ->get();

                    $tocadas = 0;

                    foreach ($cuotas as $cuota) {
                        $nueva = $credito->dueDateFor((int) $cuota->number);

                        if ($cuota->due_date?->toDateString() === $nueva->toDateString()) {
                            continue;
                        }

                        $cuota->update(['due_date' => $nueva]);
                        $tocadas++;
                    }

                    if ($tocadas > 0) {
                        $creditos++;
                        $cambiadas += $tocadas;
                    }
                }
            });

        echo "Cuotas reprogramadas: {$cambiadas} en {$creditos} créditos." . PHP_EOL;
    }

    /**
     * No se deshace: la fecha vieja era el error. El rollback es el respaldo.
     */
    public function down(): void
    {
        //
    }
};
