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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            
            // Documento
            $table->string('document_type', 30);
            $table->string('document_number', 30)->unique();

            // Información del cliente
            $table->string('full_name')->unique();
            $table->string('email')->nullable();
            $table->string('phone_primary', 20)->nullable();
            $table->string('phone_secondary', 20)->nullable();

            // Actividad económica
            $table->string('nrc')->nullable();
            $table->string('economic_activity')->nullable();

            // Ubicación
            $table->string('department');
            $table->string('municipality');
            $table->string('district');

            // Dirección
            $table->string('address');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
