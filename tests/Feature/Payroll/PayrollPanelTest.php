<?php

namespace Tests\Feature\Payroll;

use App\Filament\Admin\Resources\Administration\Payroll\Pages\CreatePayrollPeriod;
use App\Filament\Admin\Resources\Administration\Payroll\Pages\EditPayrollPeriod;
use App\Filament\Admin\Resources\Administration\Payroll\Pages\ListPayrollPeriods;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Filament\Admin\Resources\Administration\Payroll\RelationManagers\PayslipsRelationManager;
use App\Services\Payroll\GeneratePayrollService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La pantalla de planilla: crear la quincena, generar las boletas, editarlas y
 * cerrarla.
 */
class PayrollPanelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function periodo(): PayrollPeriod
    {
        return PayrollPeriod::create(
            PayrollPeriod::datesFor(2026, 4, 'first') + ['type' => 'quincenal']
        );
    }

    public function test_el_listado_y_el_alta_abren(): void
    {
        Livewire::test(ListPayrollPeriods::class)->assertOk();
        Livewire::test(CreatePayrollPeriod::class)->assertOk();
    }

    public function test_al_crear_la_quincena_las_fechas_se_calculan(): void
    {
        Livewire::test(CreatePayrollPeriod::class)
            ->fillForm([
                'year' => 2026,
                'month' => 4,
                'half' => 'second',
                'payment_date' => '2026-04-30',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $periodo = PayrollPeriod::query()->where('label', '2026-04-Q2')->firstOrFail();

        $this->assertSame('2026-04-16', $periodo->start_date->toDateString());
        $this->assertSame('2026-04-30', $periodo->end_date->toDateString());
    }

    public function test_genera_una_boleta_por_empleado_activo(): void
    {
        $periodo = $this->periodo();

        Employee::factory()->create(['name' => 'Activo Uno', 'salary_amount' => 204.40]);
        Employee::factory()->create(['name' => 'Activo Dos', 'salary_amount' => 300.00]);
        Employee::factory()->inactive()->create(['name' => 'De Baja', 'salary_amount' => 250.00]);

        Livewire::test(EditPayrollPeriod::class, ['record' => $periodo->getRouteKey()])
            ->callAction(TestAction::make('generatePayroll'))
            ->assertHasNoActionErrors();

        $this->assertSame(2, $periodo->payslips()->count());

        $boleta = $periodo->payslips()->where('employee_name', 'Activo Uno')->firstOrFail();

        $this->assertSame('6.13', $boleta->isss);
        $this->assertSame('14.82', $boleta->afp);
        $this->assertSame('20.95', $boleta->total_deductions);
        $this->assertSame('183.45', $boleta->net);
        $this->assertSame('183.45', $boleta->total_to_receive);
    }

    public function test_no_genera_dos_veces_la_boleta_del_mismo_empleado(): void
    {
        $periodo = $this->periodo();

        Employee::factory()->create(['salary_amount' => 204.40]);

        $servicio = app(GeneratePayrollService::class);

        $this->assertSame(1, $servicio->generate($periodo)['created']);
        $this->assertSame(0, $servicio->generate($periodo)['created']);
        $this->assertSame(1, $periodo->payslips()->count());
    }

    public function test_avisa_quien_no_tiene_salario(): void
    {
        $periodo = $this->periodo();

        Employee::factory()->create(['name' => 'Sin Sueldo', 'salary_amount' => 0]);
        Employee::factory()->create(['name' => 'Con Sueldo', 'salary_amount' => 204.40]);

        $resultado = app(GeneratePayrollService::class)->generate($periodo);

        $this->assertSame(1, $resultado['created']);
        $this->assertSame(['Sin Sueldo'], $resultado['without_salary']);
    }

    public function test_editar_bono_multa_y_prestamo_recalcula_los_totales(): void
    {
        $periodo = $this->periodo();
        $empleado = Employee::factory()->create(['salary_amount' => 204.40]);

        $boleta = Payslip::create([
            'period_id' => $periodo->id,
            'employee_id' => $empleado->id,
            'employee_name' => $empleado->name,
            'salary' => 204.40,
            'isss' => 6.13,
            'afp' => 14.82,
        ]);

        // Lo que el encargado escribe en la boleta: bono, multa y préstamo.
        $boleta->update([
            'bonus' => 35.60,
            'penalty' => 10,
            'penalty_reason' => 'Llegó tarde',
            'loan_deduction' => 25,
        ]);

        $boleta->refresh();

        // 6.13 ISSS + 14.82 AFP + 10 multa + 25 préstamo
        $this->assertSame('55.95', $boleta->total_deductions);
        $this->assertSame('148.45', $boleta->net);
        $this->assertSame('184.05', $boleta->total_to_receive);
        $this->assertSame('Llegó tarde', $boleta->penalty_reason);
    }

    public function test_el_formulario_de_la_quincena_muestra_sus_boletas(): void
    {
        $periodo = $this->periodo();
        $empleado = Employee::factory()->create(['name' => 'Visible En La Tabla', 'salary_amount' => 204.40]);

        app(GeneratePayrollService::class)->generate($periodo);

        // Las filas de un relation manager no están en el HTML de la página: se
        // monta el componente aparte.
        Livewire::test(PayslipsRelationManager::class, [
            'ownerRecord' => $periodo,
            'pageClass' => EditPayrollPeriod::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($periodo->payslips()->get());
    }

    public function test_marcar_pagada_sella_la_fecha_y_cierra(): void
    {
        $periodo = $this->periodo();
        $empleado = Employee::factory()->create(['salary_amount' => 204.40]);

        app(GeneratePayrollService::class)->generate($periodo);

        Livewire::test(EditPayrollPeriod::class, ['record' => $periodo->getRouteKey()])
            ->callAction(TestAction::make('markAsPaid'))
            ->assertHasNoActionErrors();

        $periodo->refresh();

        $this->assertTrue($periodo->isPaid());
        $this->assertSame('2026-04-15', $periodo->payslips()->first()->paid_at->toDateString());
    }

    public function test_un_periodo_pagado_no_regenera(): void
    {
        $periodo = $this->periodo();
        Employee::factory()->create(['salary_amount' => 204.40]);

        $servicio = app(GeneratePayrollService::class);
        $servicio->generate($periodo);
        $servicio->markAsPaid($periodo);

        $this->assertSame(0, $servicio->generate($periodo)['created']);
        $this->assertSame(1, $periodo->payslips()->count());
    }
}
