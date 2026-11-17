<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los porcentajes de ley que el encargado ajusta desde el panel.
     *
     * Una sola fila: el tope del ISSS se guarda MENSUAL (como lo dice la ley) y la
     * boleta aplica la mitad, porque se paga por quincena.
     */
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table): void {
            $table->id();

            $table->decimal('isss_rate', 5, 2)->default(3.00);
            $table->decimal('isss_cap', 10, 2)->default(30.00);
            $table->decimal('afp_rate', 5, 2)->default(7.25);

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
