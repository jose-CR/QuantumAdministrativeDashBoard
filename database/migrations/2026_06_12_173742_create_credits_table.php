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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('article_unit_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('refinanced_from_id')
                ->nullable()
                ->constrained('credits')
                ->nullOnDelete();

            $table->decimal('initial_amount', 10, 2);

            $table->decimal('down_payment', 10, 2)
                ->default(0);

            $table->decimal('financed_amount', 10, 2);

            $table->unsignedSmallInteger('installments');

            $table->decimal('installment_amount', 10, 2);

            $table->enum('periodicity', [
                'weekly',
                'monthly',
                'yearly',
            ])->default('monthly');

            $table->decimal('interest_rate', 5, 2)
                ->default(0);

            $table->decimal('total_interest', 10, 2)
                ->default(0);

            $table->decimal('total_amount', 10, 2);

            $table->decimal('pending_balance', 10, 2)
                ->default(0);

            $table->date('start_date');

            // Weekly: 1-7 (lunes-domingo)
            // Monthly: 1-31 (día del mes)
            // Yearly: 1-31 (día del mes)
            $table->unsignedTinyInteger('payment_day');

            // Yearly: 1-12 (enero-diciembre)
            $table->unsignedTinyInteger('payment_month')->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'paid',
                'refinanced',
                'cancelled',
                'defaulted',
            ])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
