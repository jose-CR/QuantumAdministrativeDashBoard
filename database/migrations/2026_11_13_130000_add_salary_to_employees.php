<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salario de UNA quincena. La boleta guarda su propia copia, así un aumento
     * no cambia las boletas que ya se entregaron.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->decimal('salary_amount', 10, 2)->default(0)->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('salary_amount');
        });
    }
};
