<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una quincena que se paga. Las fechas se calculan (1-15 y 16-fin de mes), no
     * se escriben a mano.
     */
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();

            $table->string('label', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date');
            $table->string('type', 20)->default('quincenal');

            // open = se puede regenerar; paid = boletas entregadas, no se toca.
            $table->string('status', 20)->default('open');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
