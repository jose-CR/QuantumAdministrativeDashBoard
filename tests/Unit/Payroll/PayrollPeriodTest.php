<?php

namespace Tests\Unit\Payroll;

use App\Models\PayrollPeriod;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Las fechas de la quincena salen del calendario: 15, y 28/29/30/31 según el mes.
 */
class PayrollPeriodTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_primera_quincena_termina_el_15(): void
    {
        $fechas = PayrollPeriod::datesFor(2026, 11, 'first');

        $this->assertSame('2026-11-Q1', $fechas['label']);
        $this->assertSame('2026-11-01', $fechas['start_date']);
        $this->assertSame('2026-11-15', $fechas['end_date']);
    }

    public function test_la_segunda_termina_el_ultimo_dia_del_mes(): void
    {
        $this->assertSame('2026-11-30', PayrollPeriod::datesFor(2026, 11, 'second')['end_date']);
        $this->assertSame('2026-12-31', PayrollPeriod::datesFor(2026, 12, 'second')['end_date']);
        // Febrero bisiesto y no bisiesto.
        $this->assertSame('2028-02-29', PayrollPeriod::datesFor(2028, 2, 'second')['end_date']);
        $this->assertSame('2026-02-28', PayrollPeriod::datesFor(2026, 2, 'second')['end_date']);
    }

    public function test_una_quincena_no_se_repite(): void
    {
        $fechas = PayrollPeriod::datesFor(2026, 3, 'first');

        PayrollPeriod::create($fechas + ['type' => 'quincenal']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        PayrollPeriod::create($fechas + ['type' => 'quincenal']);
    }

    public function test_la_fecha_de_la_boleta_lleva_el_periodo_completo(): void
    {
        $periodo = PayrollPeriod::create(
            PayrollPeriod::datesFor(2026, 11, 'first') + ['type' => 'quincenal']
        );

        $this->assertSame('01 DE NOVIEMBRE AL 15 DE NOVIEMBRE 2026', $periodo->payslip_date);

        $segunda = PayrollPeriod::create(
            PayrollPeriod::datesFor(2026, 11, 'second') + ['type' => 'quincenal']
        );

        $this->assertSame('16 DE NOVIEMBRE AL 30 DE NOVIEMBRE 2026', $segunda->payslip_date);
    }

    public function test_la_fecha_de_la_boleta_se_escribe_en_palabras(): void
    {
        $periodo = PayrollPeriod::create(
            PayrollPeriod::datesFor(2026, 2, 'first') + ['type' => 'quincenal']
        );

        $this->assertSame('01 DE FEBRERO AL 15 DE FEBRERO 2026', $periodo->payslip_date);
    }
}
