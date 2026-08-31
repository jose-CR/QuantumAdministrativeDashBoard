<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_id')
                ->constrained()
                ->cascadeOnDelete();

            // Número de cuota
            $table->unsignedSmallInteger('number');

            // Valor de la cuota
            $table->decimal('amount', 10, 2);

            // Fecha de vencimiento
            $table->date('due_date');

            // Fecha de pago real
            $table->date('paid_at')
                ->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'late',
                'cancelled',
                'refinanced',
                'completed',
            ])->default('pending');

            $table->decimal('remaining_balance', 10, 2)
                ->default(0);

            $table->unique([
                'credit_id',
                'number',
            ]);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
