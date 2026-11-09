<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los bienes de un crédito pueden ser artículos que se llevan por cantidad
 * (un casco, una batería suelta): el crédito no apunta a una unidad con serie,
 * apunta al artículo y a cuántos se llevó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')
                ->default(1)
                ->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('credit_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
