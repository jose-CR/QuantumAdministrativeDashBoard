<?php

namespace Tests\Unit\Employees;

use App\Models\Employee;
use App\Support\JobTitles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El encargado puede agregar un puesto nuevo además de los del catálogo.
 */
class JobTitlesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_lista_trae_el_catalogo_y_los_que_ya_se_usaron(): void
    {
        Employee::factory()->create(['job_title' => 'soldador']);

        $puestos = JobTitles::all();

        foreach (Employee::JOB_TITLES as $puesto) {
            $this->assertArrayHasKey($puesto, $puestos);
        }

        $this->assertArrayHasKey('soldador', $puestos);
        $this->assertSame('Soldador', $puestos['soldador']);
    }

    public function test_los_del_catalogo_salen_traducidos(): void
    {
        $puestos = JobTitles::all();

        $this->assertSame(__('resources.employees.job_titles.tecnico'), $puestos['tecnico']);
    }

    public function test_un_puesto_nuevo_no_se_duplica_por_mayusculas(): void
    {
        Employee::factory()->create(['job_title' => 'Soldador']);

        $this->assertSame('Soldador', JobTitles::normalize('  soldador '));
        $this->assertSame('Pintor', JobTitles::normalize('Pintor'));
        $this->assertNull(JobTitles::normalize('   '));
    }

    public function test_el_buscador_filtra_por_el_nombre(): void
    {
        Employee::factory()->create(['job_title' => 'soldador']);

        $this->assertArrayHasKey('soldador', JobTitles::search('sold'));
        $this->assertArrayNotHasKey('soldador', JobTitles::search('vende'));
    }
}
