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
        Schema::create('article_units', function (Blueprint $table) {
            $table->id();

            $table->foreignId('article_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('color')->nullable();

            $table->decimal('cash_price', 10, 2);

            $table->string('vin', 17)
                ->nullable()
                ->unique();

            $table->string('engine_number')
                ->nullable();

            $table->string('plate')
                ->nullable()
                ->unique();

            $table->enum('status', [
                'available',
                'reserved',
                'sold',
            ])->default('available');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_units');
    }
};
