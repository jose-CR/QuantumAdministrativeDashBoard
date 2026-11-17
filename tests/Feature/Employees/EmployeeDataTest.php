<?php

namespace Tests\Feature\Employees;

use App\Filament\Exports\Administration\EmployeesExporter;
use App\Filament\Imports\Administration\EmployeeImporter;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El Excel de empleados: el export no puede escribir columnas que no existen, y el
 * import tiene que aceptar el mismo archivo traducido que baja.
 */
class EmployeeDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_export_no_trae_columnas_fantasma(): void
    {
        $columnas = collect(EmployeesExporter::getColumns())->map(fn ($columna): string => $columna->getName());
        $reales = Schema::getColumnListing('employees');

        foreach ($columnas as $nombre) {
            if (str_contains($nombre, '.')) {
                // Relación (user.name): se comprueba que el dueño exista.
                [$relacion] = explode('.', $nombre);
                $this->assertTrue(method_exists(Employee::class, $relacion), "Falta la relación {$relacion}");

                continue;
            }

            $this->assertContains($nombre, $reales, "El export escribe {$nombre} y no existe en employees.");
        }
    }

    public function test_el_export_cubre_lo_que_se_ve_en_la_pantalla(): void
    {
        $nombres = collect(EmployeesExporter::getColumns())->map(fn ($columna): string => $columna->getName())->all();

        foreach (['code', 'name', 'document_type', 'document_number', 'job_title', 'phone', 'email', 'hire_date', 'status'] as $columna) {
            $this->assertContains($columna, $nombres);
        }
    }

    public function test_el_import_acepta_las_etiquetas_traducidas(): void
    {
        $columnas = collect(EmployeeImporter::getColumns())
            ->mapWithKeys(fn ($columna): array => [$columna->getName() => $columna]);

        // Tal como salen del export, y también como valor crudo de la base.
        $this->assertSame('tecnico', $columnas['job_title']->castState('Técnico'));
        $this->assertSame('tecnico', $columnas['job_title']->castState('tecnico'));
        $this->assertSame('inactive', $columnas['status']->castState('De baja'));
        $this->assertSame('active', $columnas['status']->castState('Activo'));
        $this->assertSame('DUI', $columnas['document_type']->castState('DUI'));
        $this->assertNull($columnas['code']->castState(''));
    }
}
