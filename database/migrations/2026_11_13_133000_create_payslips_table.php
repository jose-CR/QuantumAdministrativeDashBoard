<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La boleta de pago de un empleado en una quincena.
     *
     * `employee_name` y `salary` son copias a propósito: la boleta es un documento
     * firmado y no puede cambiar porque después se corrija el nombre o el sueldo.
     */
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('payroll_periods')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->string('employee_name');

            $table->decimal('salary', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);

            // Descuentos de ley, calculados al generar.
            $table->decimal('isss', 10, 2)->default(0);
            $table->decimal('afp', 10, 2)->default(0);

            // Lo que escribe el encargado.
            $table->decimal('penalty', 10, 2)->default(0);
            $table->string('penalty_reason')->nullable();
            $table->decimal('loan_deduction', 10, 2)->default(0);

            // Totales, congelados con el resto.
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net', 10, 2)->default(0);
            $table->decimal('total_to_receive', 10, 2)->default(0);

            $table->date('paid_at')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Un empleado cobra una sola vez por quincena.
            $table->unique(['period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
