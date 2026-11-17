<?php

namespace Tests\Feature\Employees;

use App\Filament\Admin\Resources\Administration\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\Administration\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Administration\Employees\Pages\ListEmployees;
use App\Models\Employee;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La pantalla de empleados: listado, alta, edición y la baja (que no borra).
 */
class EmployeePanelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_el_listado_abre_y_muestra_empleados(): void
    {
        $empleado = Employee::factory()->create(['name' => 'Vendedor Visible']);

        Livewire::test(ListEmployees::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$empleado]);
    }

    public function test_se_puede_crear_un_empleado(): void
    {
        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'code' => 'EMP-900',
                'name' => 'Empleado Nuevo',
                'document_type' => 'DUI',
                'document_number' => '045190090',
                'job_title' => 'tecnico',
                'status' => Employee::STATUS_ACTIVE,
                'hire_date' => today()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $empleado = Employee::query()->where('name', 'Empleado Nuevo')->firstOrFail();

        $this->assertSame('tecnico', $empleado->job_title);
        $this->assertTrue($empleado->isActive());
        $this->assertSame('045190090', $empleado->document_number);
    }

    public function test_se_puede_crear_un_empleado_con_un_puesto_nuevo(): void
    {
        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'code' => 'EMP-910',
                'name' => 'Soldador Nuevo',
                'document_type' => 'DUI',
                'document_number' => '045190910',
                'job_title' => 'soldador',
                'status' => Employee::STATUS_ACTIVE,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $empleado = Employee::query()->where('name', 'Soldador Nuevo')->firstOrFail();

        $this->assertSame('soldador', $empleado->job_title);
        // Un puesto que no está en el catálogo se muestra tal cual, no como su clave.
        $this->assertSame('Soldador', \App\Support\JobTitles::label('soldador'));
    }

    public function test_el_codigo_y_el_documento_no_se_repiten(): void
    {
        Employee::factory()->create(['code' => 'EMP-901']);
        Employee::factory()->create(['document_type' => 'DUI', 'document_number' => '045190091']);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'code' => 'EMP-901',
                'name' => 'Otro Empleado',
                'document_type' => 'DUI',
                'document_number' => '045190091',
                'job_title' => 'vendedor',
                'status' => Employee::STATUS_ACTIVE,
            ])
            ->call('create')
            ->assertHasFormErrors(['code', 'document_number']);
    }

    public function test_se_puede_editar(): void
    {
        $empleado = Employee::factory()->create(['job_title' => 'vendedor']);

        Livewire::test(EditEmployee::class, ['record' => $empleado->getRouteKey()])
            ->fillForm(['job_title' => 'bodega'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('bodega', $empleado->fresh()->job_title);
    }

    public function test_dar_de_baja_no_borra_y_guarda_la_fecha(): void
    {
        $empleado = Employee::factory()->create();

        Livewire::test(ListEmployees::class)
            ->callAction(TestAction::make('deactivate')->table($empleado))
            ->assertHasNoActionErrors();

        $empleado->refresh();

        $this->assertSame(Employee::STATUS_INACTIVE, $empleado->status);
        $this->assertNotNull($empleado->termination_date);
        $this->assertNull($empleado->deleted_at);
    }

    public function test_un_empleado_con_ventas_no_se_puede_borrar(): void
    {
        $empleado = Employee::factory()->create(['name' => 'Vendedor Con Ventas']);

        \App\Models\Inflow::factory()->create([
            'salesperson' => 'Vendedor Con Ventas',
            'customer_id' => \App\Models\Customer::factory()->create()->id,
        ]);

        $this->assertTrue($empleado->hasHistory());
        $this->assertFalse($empleado->delete(), 'Se borró un empleado con ventas.');
        $this->assertNotNull(Employee::find($empleado->id));

        // El botón de borrar no aparece para él, pero sí el de dar de baja.
        Livewire::test(ListEmployees::class)
            ->assertTableActionHidden('delete', $empleado)
            ->assertTableActionVisible('deactivate', $empleado);
    }

    public function test_un_empleado_sin_ventas_si_se_puede_borrar(): void
    {
        $empleado = Employee::factory()->create(['name' => 'Nadie Lo Uso']);

        $this->assertFalse($empleado->hasHistory());
        $empleado->delete();

        $this->assertSoftDeleted($empleado);
    }

    public function test_reactivar_limpia_la_fecha_de_baja(): void
    {
        $empleado = Employee::factory()->inactive()->create();

        Livewire::test(ListEmployees::class)
            ->callAction(TestAction::make('reactivate')->table($empleado))
            ->assertHasNoActionErrors();

        $empleado->refresh();

        $this->assertTrue($empleado->isActive());
        $this->assertNull($empleado->termination_date);
    }
}
