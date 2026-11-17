<?php

namespace Tests\Unit\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El empleado es la persona del taller, separada del usuario del panel.
 */
class EmployeeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_factory_crea_un_empleado_activo(): void
    {
        $empleado = Employee::factory()->create();

        $this->assertTrue($empleado->isActive());
        $this->assertSame(Employee::STATUS_ACTIVE, $empleado->status);
        $this->assertNotNull($empleado->code);
        $this->assertNotNull($empleado->hire_date);
        $this->assertNull($empleado->termination_date);
    }

    public function test_la_factory_tiene_estado_inactivo(): void
    {
        $empleado = Employee::factory()->inactive()->create();

        $this->assertFalse($empleado->isActive());
        $this->assertNotNull($empleado->termination_date);
    }

    public function test_el_documento_no_se_repite(): void
    {
        Employee::factory()->create([
            'document_type' => 'DUI',
            'document_number' => '04511111-1',
        ]);

        $this->expectException(QueryException::class);

        Employee::factory()->create([
            'document_type' => 'DUI',
            'document_number' => '04511111-1',
        ]);
    }

    public function test_varios_empleados_sin_documento(): void
    {
        // El documento es opcional (los nombres viejos no lo traen) y en Postgres
        // varios NULL no chocan contra el índice único.
        Employee::factory()->count(2)->create([
            'document_type' => null,
            'document_number' => null,
        ]);

        $this->assertSame(2, Employee::query()->whereNull('document_number')->count());
    }

    public function test_el_alcance_activo_deja_fuera_a_los_de_baja(): void
    {
        $activo = Employee::factory()->create();
        $inactivo = Employee::factory()->inactive()->create();

        $ids = Employee::query()->active()->pluck('id')->all();

        $this->assertContains($activo->id, $ids);
        $this->assertNotContains($inactivo->id, $ids);
    }

    public function test_se_puede_vincular_al_usuario_del_panel(): void
    {
        $usuario = $this->admin();
        $empleado = Employee::factory()->create(['user_id' => $usuario->id]);

        $this->assertSame($usuario->id, $empleado->user->id);
    }

    public function test_el_borrado_es_logico(): void
    {
        $empleado = Employee::factory()->create();

        $empleado->delete();

        $this->assertSoftDeleted($empleado);
        // Sigue existiendo para el histórico de ventas.
        $this->assertNotNull(Employee::withTrashed()->find($empleado->id));
    }
}
