<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vuelve el inventario genérico: un artículo puede ser un vehículo (se controla
 * por número de serie / VIN, una fila por unidad) o cualquier otra cosa que se
 * guarde en bodega (casco, batería, repuesto), donde no hay VIN y lo que importa
 * es la cantidad.
 *
 * El usuario decide el criterio al crear el artículo, con la columna `tracking`:
 *   - serial : cada pieza es un registro en `article_units` y exige serie/VIN.
 *   - none   : no se crean unidades; se maneja `stock` en el artículo.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('name')->nullable()->after('category_id');
            $table->string('sku', 64)->nullable()->unique()->after('name');
            $table->string('tracking', 10)->default('none')->after('sku');
            $table->decimal('cash_price', 10, 2)->nullable()->after('year');
            $table->unsignedInteger('stock')->default(0)->after('cash_price');
        });

        // Nombre legible para todo lo que ya existe (vehículos: marca modelo año).
        DB::statement("
            UPDATE articles
               SET name = NULLIF(TRIM(CONCAT_WS(' ', brand, model, year)), '')
        ");

        // Lo que hoy tiene unidades es un artículo serializado: ya se controla
        // una fila por pieza con su VIN.
        DB::statement("
            UPDATE articles
               SET tracking = 'serial'
             WHERE EXISTS (SELECT 1 FROM article_units u WHERE u.article_id = articles.id)
        ");

        // Marca, modelo y año dejan de ser obligatorios: un casco no tiene año.
        Schema::table('articles', function (Blueprint $table) {
            $table->string('brand')->nullable()->change();
            $table->string('model')->nullable()->change();
            $table->year('year')->nullable()->change();
        });

        // La llave natural pasa a ser (categoría, nombre). El año ya no distingue
        // y, al permitir nulos, el índice viejo dejaba pasar duplicados.
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'brand', 'model', 'year']);
            $table->unique(['category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'name']);
            $table->dropUnique(['sku']);
            $table->dropColumn(['name', 'sku', 'tracking', 'cash_price', 'stock']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('brand')->nullable(false)->change();
            $table->string('model')->nullable(false)->change();
            $table->year('year')->nullable(false)->change();
            $table->unique(['category_id', 'brand', 'model', 'year']);
        });
    }
};
