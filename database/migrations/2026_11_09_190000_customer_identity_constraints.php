<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La identidad de un cliente es su documento, no su nombre.
 *
 * `full_name` era UNIQUE: dos personas distintas con el mismo nombre no podían
 * registrarse, y el formulario no lo avisaba (error 500 al guardar). El número de
 * documento sí es único, pero por tipo: el NIT de una empresa puede coincidir con
 * el número de pasaporte de otra persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_full_name_unique');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_document_number_unique');
            $table->unique(['document_type', 'document_number'], 'customers_document_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_document_unique');
            $table->unique('document_number', 'customers_document_number_unique');
            $table->unique('full_name', 'customers_full_name_unique');
        });
    }
};
