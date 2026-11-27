<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Imágenes del inventario, en dos mundos separados a propósito:
 *
 *  - `article_unit_images`: las fotos de una unidad concreta (un carrito, una batería
 *    con su VIN). Una unidad puede tener varias.
 *  - `gallery_images`: lo que no pertenece a ninguna unidad (fotos del taller, de un
 *    repuesto suelto, de un daño), clasificado por categoría.
 *
 * Las dos usan los mismos campos de publicación que `attachments`, para que la ruta
 * /img/{token} y el botón «Obtener URL» funcionen igual en las tres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_unit_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->unsignedSmallInteger('sort')->default(0);

            // Publicación: mismo esquema que attachments (token, no id).
            $table->boolean('is_public')->default(false);
            $table->string('public_token', 40)->nullable()->unique();
            $table->string('private_disk', 40)->nullable();
            $table->string('private_path')->nullable();

            $table->timestamps();

            $table->index(['article_unit_id', 'sort']);
        });

        Schema::create('gallery_images', function (Blueprint $table): void {
            $table->id();
            // La categoría es la de Inventario (product.category): si se borra, no
            // puede arrastrar imágenes sin avisar.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('path');
            $table->unsignedSmallInteger('sort')->default(0);

            $table->boolean('is_public')->default(false);
            $table->string('public_token', 40)->nullable()->unique();
            $table->string('private_disk', 40)->nullable();
            $table->string('private_path')->nullable();

            $table->timestamps();

            $table->index(['category_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('article_unit_images');
    }
};
