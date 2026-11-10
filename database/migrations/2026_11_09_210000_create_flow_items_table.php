<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Artículos que se mueven con una entrada o salida de caja.
 *
 * Hasta acá una venta de contado era solo un monto: entraba la plata y el stock
 * no se movía. Lo mismo una compra. Estas líneas son las que mueven inventario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_items', function (Blueprint $table) {
            $table->id();

            $table->morphs('flow');

            $table->foreignId('article_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('price', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_items');
    }
};
