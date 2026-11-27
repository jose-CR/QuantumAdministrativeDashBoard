<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un adjunto puede publicarse: sale de la carpeta privada, pasa a `media` y recibe
 * un token aleatorio con el que se arma su URL pública (/img/{token}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('size');
            $table->string('public_token', 40)->nullable()->unique()->after('is_public');
            // Dónde estaba antes de publicarse, para devolverlo tal cual.
            $table->string('private_disk', 40)->nullable()->after('public_token');
            $table->string('private_path')->nullable()->after('private_disk');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'public_token', 'private_disk', 'private_path']);
        });
    }
};
