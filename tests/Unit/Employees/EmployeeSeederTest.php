<?php

namespace Tests\Unit\Employees;

use App\Models\Employee;
use App\Models\Inflow;
use Database\Seeders\EmployeeSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El seeder saca los empleados de los vendedores que ya están escritos en las
 * entradas de caja, sin inventar gente y sin duplicar si se corre dos veces.
 */
class EmployeeSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_convierte_los_vendedores_de_las_entradas_en_empleados(): void
    {
        $distintos = Inflow::query()
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->distinct()
            ->count('salesperson');

        $this->seed(EmployeeSeeder::class);

        $this->assertSame($distintos, Employee::query()->count());
        $this->assertSame($distintos, Employee::query()->where('job_title', 'vendedor')->count());
    }

    public function test_correrlo_dos_veces_no_duplica(): void
    {
        $this->seed(EmployeeSeeder::class);
        $primera = Employee::query()->count();

        $this->seed(EmployeeSeeder::class);

        $this->assertSame($primera, Employee::query()->count());
    }
}
