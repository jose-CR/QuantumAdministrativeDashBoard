<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Empleados del taller: la persona (vendedor, técnico, bodega) separada del
     * usuario que entra al panel. La mayoría nunca tendrá login, así que `user_id`
     * es opcional; cuando lo tiene, es la misma persona y no se duplica el nombre.
     *
     * Las boletas de pago NO van acá: quedan para una tabla aparte cuando se
     * implementen (ver docs/futuro-pagos-empleados.md).
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();

            // Código interno / de nómina. Opcional: los que ya existen (los nombres
            // que venían escritos en las entradas) no lo tienen.
            $table->string('code', 20)->nullable()->unique();

            $table->string('name');

            // La identidad es el documento, igual que en clientes. Nulo permitido
            // porque los nombres viejos solo traen el nombre; el único aplica cuando
            // hay valor (Postgres deja varios NULL en un índice único).
            $table->string('document_type', 20)->nullable();
            $table->string('document_number', 50)->nullable();
            $table->unique(['document_type', 'document_number']);

            $table->string('phone', 25)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Qué hace en el taller. Los valores viven en Employee::JOB_TITLES.
            $table->string('job_title', 30)->default('vendedor');

            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();

            // Alta o baja. Un empleado con historial no se borra: se pasa a inactivo.
            $table->string('status', 20)->default('active');

            // El login, si lo tiene. Una persona = un usuario.
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
