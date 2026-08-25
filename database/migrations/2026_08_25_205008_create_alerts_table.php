<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('credit_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('installment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // upcoming_payment | reminder | follow_up | custom
            $table->string('type');

            $table->string('title');

            $table->text('message');

            // Día en que debe aparecer la alerta
            $table->dateTime('alert_at');

            // pending | completed | cancelled
            $table->string('status')
                ->default('pending');

            // Para no volver a enviarla
            $table->dateTime('shown_at')
                ->nullable();

            // para ver a donde se envio
            $table->dateTime('sent_at')
                ->nullable();

            // Información extra
            $table->json('metadata')
                ->nullable();

            $table->index(['assigned_user_id', 'status']);
            
            $table->index('alert_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
