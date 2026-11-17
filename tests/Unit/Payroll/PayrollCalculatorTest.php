<?php

namespace Tests\Unit\Payroll;

use App\Models\Employee;
use App\Models\PayrollSetting;
use App\Support\Payroll\PayrollCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Las cuentas de la boleta, con los números del taller.
 */
class PayrollCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollSetting::current()->update([
            'isss_rate' => 3.00,
            'isss_cap' => 30.00,
            'afp_rate' => 7.25,
        ]);
    }

    public function test_isss_y_afp_sobre_el_salario_de_la_quincena(): void
    {
        $this->assertSame(6.13, PayrollCalculator::isss(204.40));
        $this->assertSame(14.82, PayrollCalculator::afp(204.40));
    }

    public function test_el_isss_respeta_el_tope_de_ley(): void
    {
        // El tope se carga mensual ($30) y la boleta usa la mitad: $15.
        $this->assertSame(15.0, PayrollCalculator::isss(900.00));
        $this->assertSame(15.0, PayrollCalculator::isss(5_000.00));
    }

    public function test_el_afp_no_tiene_tope(): void
    {
        $this->assertSame(362.5, PayrollCalculator::afp(5_000.00));
    }

    public function test_los_totales_de_la_boleta(): void
    {
        $totales = PayrollCalculator::totals(
            salary: 204.40,
            bonus: 35.60,
            isss: 6.13,
            afp: 14.82,
            penalty: 0,
            loan: 0,
        );

        $this->assertSame(20.95, $totales['total_deductions']);
        $this->assertSame(183.45, $totales['net']);
        $this->assertSame(219.05, $totales['total_to_receive']);
    }

    public function test_la_multa_y_el_prestamo_bajan_el_neto(): void
    {
        $totales = PayrollCalculator::totals(300.0, 0.0, 9.0, 21.75, 20.0, 50.0);

        $this->assertSame(100.75, $totales['total_deductions']);
        $this->assertSame(199.25, $totales['net']);
        $this->assertSame(199.25, $totales['total_to_receive']);
    }

    public function test_los_parametros_se_ajustan_sin_tocar_codigo(): void
    {
        PayrollSetting::current()->update(['isss_rate' => 5.00, 'afp_rate' => 10.00]);

        $this->assertSame(10.22, PayrollCalculator::isss(204.40));
        $this->assertSame(20.44, PayrollCalculator::afp(204.40));
    }
}
