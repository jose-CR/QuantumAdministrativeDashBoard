<?php

namespace Tests\Feature\Payroll;

use App\Filament\Admin\Resources\Administration\Payroll\Pages\ListPayrollPeriods;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Services\Payroll\GeneratePayrollService;
use App\Services\Payroll\PayslipExcelService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/**
 * El Excel de la quincena con la plantilla del taller, y el modal de parámetros.
 */
class PayslipExcelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function periodoConBoletas(): PayrollPeriod
    {
        $periodo = PayrollPeriod::create(
            PayrollPeriod::datesFor(2026, 5, 'first') + ['type' => 'quincenal']
        );

        Employee::factory()->create(['name' => 'Boleta Uno', 'salary_amount' => 204.40]);
        Employee::factory()->create(['name' => 'Boleta Dos', 'salary_amount' => 300.00]);

        app(GeneratePayrollService::class)->generate($periodo);

        return $periodo;
    }

    public function test_genera_un_solo_excel_con_todas_las_boletas(): void
    {
        $periodo = $this->periodoConBoletas();

        $ruta = app(PayslipExcelService::class)->generate($periodo);

        try {
            $this->assertFileExists($ruta);
            $this->assertGreaterThan(0, filesize($ruta));

            // Un xlsx es un zip: si abre y trae la hoja de boletas, el archivo es válido.
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($ruta) === true);
            $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));

            // openpyxl escribe los textos dentro de la hoja (inline strings).
            $hoja = $zip->getFromName('xl/worksheets/sheet1.xml') ?: '';
            $this->assertStringContainsString('Boleta Uno', $hoja);
            $this->assertStringContainsString('Boleta Dos', $hoja);
            $this->assertStringContainsString('204.4', $hoja);
            $zip->close();
        } finally {
            File::delete($ruta);
        }
    }

    public function test_sin_boletas_no_genera_el_archivo(): void
    {
        $periodo = PayrollPeriod::create(
            PayrollPeriod::datesFor(2026, 6, 'first') + ['type' => 'quincenal']
        );

        $this->expectException(ValidationException::class);

        app(PayslipExcelService::class)->generate($periodo);
    }

    public function test_el_encargado_ajusta_los_parametros_desde_el_modal(): void
    {
        PayrollSetting::current()->update(['isss_rate' => 3.00, 'isss_cap' => 30.00, 'afp_rate' => 7.25]);

        Livewire::test(ListPayrollPeriods::class)
            ->callAction(TestAction::make('payrollSettings'), [
                'isss_rate' => 3.00,
                'isss_cap' => 45.00,
                'afp_rate' => 7.25,
            ])
            ->assertHasNoActionErrors();

        $parametros = PayrollSetting::current();

        $this->assertSame('45.00', $parametros->isss_cap);
        $this->assertNotNull($parametros->updated_by);
    }
}
