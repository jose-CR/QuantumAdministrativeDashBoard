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
        Schema::create('credit_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('credit_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('article_unit_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('price', 10, 2);

            $table->timestamps();

            $table->unique([
                'credit_id',
                'article_unit_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_items');
    }
};
