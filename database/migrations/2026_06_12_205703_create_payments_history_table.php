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
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnDelete();
            ;

            // Monto abonado
            $table->decimal('amount', 10, 2);

            // Metodo de pago
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'card',
            ]);

            // Fecha efectiva del pago
            $table->date('payment_date');

            // Número de recibo o comprobante
            $table->string('receipt_number')
                ->nullable();

            // balance previo
            $table->decimal('previous_balance', 10, 2);

            // nuevo balance
            $table->decimal('new_balance', 10, 2);

            // Observaciones
            $table->text('notes')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
