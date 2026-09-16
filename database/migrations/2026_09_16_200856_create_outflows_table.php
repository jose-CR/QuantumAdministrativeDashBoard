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
        Schema::create('outflows', function (Blueprint $table) {
            $table->id();
            $table->date('invoice_date');
            $table->string('company');
            $table->string('invoice_code');
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->string('source')->default('Caja');
            $table->string('area');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outflows');
    }
};
