<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las imágenes del inventario vuelven a `attachments`.
 *
 * La tabla ya es polimórfica y genérica: una foto de una unidad es un adjunto cuyo
 * dueño es la unidad, y una foto suelta es un adjunto sin dueño con una categoría.
 * No hacían falta dos tablas nuevas (y con ellas, dos modelos y dos copias de la
 * lógica de publicar).
 *
 * Se borran `article_unit_images` y `gallery_images`, que ya no se usan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            // Solo para las fotos sueltas de la galería: la categoría de Inventario.
            $table->foreignId('category_id')->nullable()->after('attachable_id')
                ->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sort')->default(0)->after('size');
        });

        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('article_unit_images');
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('sort');
        });
    }
};
