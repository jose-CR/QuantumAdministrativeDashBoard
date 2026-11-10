<?php

namespace Tests\Feature\Reports;

use App\Support\CashReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * El reporte de caja se genera de verdad: python, las dos hojas de flujo y las dos
 * extra (cartera e inventario).
 */
class CashReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_reporte_se_genera_con_las_cuatro_hojas(): void
    {
        $ruta = CashReport::generate();

        try {
            $this->assertFileExists($ruta);
            $this->assertGreaterThan(10_000, filesize($ruta), 'El Excel salió demasiado chico.');

            $hojas = Process::run([
                'python3',
                '-c',
                'from openpyxl import load_workbook; import sys; '
                . 'print(",".join(load_workbook(sys.argv[1]).sheetnames))',
                $ruta,
            ]);

            $this->assertTrue($hojas->successful(), 'No se pudo abrir el Excel: ' . $hojas->errorOutput());

            foreach (['Reporte', 'Resumen', 'Análisis', 'Cartera', 'Inventario'] as $hoja) {
                $this->assertStringContainsString($hoja, $hojas->output());
            }
        } finally {
            File::delete($ruta);
        }
    }

    public function test_sin_datos_en_el_rango_avisa(): void
    {
        $this->expectException(\RuntimeException::class);

        CashReport::generate('2099-01-01', '2099-01-31');
    }
}
