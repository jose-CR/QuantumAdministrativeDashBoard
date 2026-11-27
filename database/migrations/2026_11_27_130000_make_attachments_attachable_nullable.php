<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una foto de la galería no tiene dueño: es un adjunto suelto.
 *
 * `attachable_type` / `attachable_id` eran obligatorios porque todo adjunto colgaba de
 * un cliente, un crédito o una cuota. Ahora hay adjuntos del inventario sin dueño, así
 * que pasan a ser nulables (las filas existentes no cambian).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->string('attachable_type')->nullable()->change();
            $table->unsignedBigInteger('attachable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->string('attachable_type')->nullable(false)->change();
            $table->unsignedBigInteger('attachable_id')->nullable(false)->change();
        });
    }
};
