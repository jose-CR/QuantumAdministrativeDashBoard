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
        Schema::create('client_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('reference_type', [
                'family',
                'friend',
            ]);

            $table->string('full_name');

            $table->string('relationship')->nullable();

            $table->string('phone');

            $table->string('address')->nullable();

            $table->string('occupation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_references');
    }
};
