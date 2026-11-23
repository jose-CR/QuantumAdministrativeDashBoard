<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de documento de cada entrada y salida de caja (CF, CCF, sujeto excluido…).
 *
 * Va como columna y no como etiqueta: es una lista cerrada y fiscal, y el CHECK de la
 * base es la única garantía de que no entre `C.F.` escrito a mano. La lista viene de
 * App\Support\DocumentTypes y tiene que coincidir con ella.
 *
 * Es nulable a propósito: las filas viejas no tienen el dato y no se les inventa — la
 * pantalla las muestra como «Sin especificar».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inflows', function (Blueprint $table): void {
            $table->string('document_type', 20)->nullable()->after('invoice_number');
        });

        Schema::table('outflows', function (Blueprint $table): void {
            $table->string('document_type', 20)->nullable()->after('invoice_code');
        });

        DB::statement("
            alter table inflows add constraint inflows_document_type_check
            check (document_type is null or document_type in ('cf', 'ccf', 'excluded', 'receipt'))
        ");

        DB::statement("
            alter table outflows add constraint outflows_document_type_check
            check (document_type is null or document_type in ('invoice', 'ccf', 'cf', 'receipt'))
        ");
    }

    public function down(): void
    {
        DB::statement('alter table inflows drop constraint if exists inflows_document_type_check');
        DB::statement('alter table outflows drop constraint if exists outflows_document_type_check');

        Schema::table('inflows', fn (Blueprint $table) => $table->dropColumn('document_type'));
        Schema::table('outflows', fn (Blueprint $table) => $table->dropColumn('document_type'));
    }
};
