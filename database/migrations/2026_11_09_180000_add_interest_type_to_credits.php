<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El interés de un crédito puede ser el que sale del cálculo (según la cuota) o
 * uno fijo negociado con el cliente. Sin esta columna no había forma de saber,
 * al leer un crédito viejo, si la tasa se calculó o se pactó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->string('interest_type', 20)
                ->default('auto')
                ->after('installment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn('interest_type');
        });
    }
};
