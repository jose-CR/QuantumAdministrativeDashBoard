<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El tipo de documento pasa de lista fija en el código a catálogo editable.
 *
 * Antes: un `CHECK` con cinco códigos y una clase con la lista escrita. Agregar un tipo
 * exigía código y migración. Ahora vive en `document_types` y se administra desde
 * Configuración → Tipos de documento.
 *
 * Se quita el `CHECK` de `inflows`/`outflows` porque cerraba la lista; la validación
 * queda en el catálogo (formulario e import) y en la columna `code` de la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();   // lo que se guarda en la caja
            $table->string('name');                 // lo que se ve
            // Dónde aplica: una entrada no lleva «factura de compra».
            $table->string('scope', 10)->default('both'); // inflow | outflow | both
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['scope', 'active']);
        });

        // Los cinco que ya existían, con el mismo orden que tenían.
        $tipos = [
            ['code' => 'cf', 'name' => 'Consumidor Final', 'scope' => 'both', 'sort' => 1],
            ['code' => 'ccf', 'name' => 'Comprobante de Crédito Fiscal', 'scope' => 'both', 'sort' => 2],
            ['code' => 'excluded', 'name' => 'Factura de sujeto excluido', 'scope' => 'inflow', 'sort' => 3],
            ['code' => 'receipt', 'name' => 'Recibo interno', 'scope' => 'both', 'sort' => 4],
            ['code' => 'invoice', 'name' => 'Factura de compra', 'scope' => 'outflow', 'sort' => 5],
        ];

        foreach ($tipos as $tipo) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $tipo['code']],
                [...$tipo, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        DB::statement('alter table inflows drop constraint if exists inflows_document_type_check');
        DB::statement('alter table outflows drop constraint if exists outflows_document_type_check');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');

        DB::statement("
            alter table inflows add constraint inflows_document_type_check
            check (document_type is null or document_type in ('cf', 'ccf', 'excluded', 'receipt'))
        ");

        DB::statement("
            alter table outflows add constraint outflows_document_type_check
            check (document_type is null or document_type in ('invoice', 'ccf', 'cf', 'receipt'))
        ");
    }
};
