<?php

namespace Tests\Feature\Reports;

use App\Models\Inflow;
use App\Models\Outflow;
use App\Support\CashReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * El resumen mensual no debe llenarse de meses en cero por una fecha mal cargada.
 */
class CashReportMonthsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_resumen_solo_lista_meses_con_movimiento(): void
    {
        // Un año equivocado en una factura no puede abrir el libro seis años antes.
        Inflow::factory()->create([
            'date' => '2020-12-20',
            'amount' => 400,
            'amount_paid' => 400,
        ]);

        Inflow::factory()->create([
            'date' => now()->startOfMonth()->toDateString(),
            'amount' => 100,
            'amount_paid' => 100,
        ]);

        Outflow::factory()->create([
            'invoice_date' => now()->startOfMonth()->toDateString(),
            'amount' => 50,
        ]);

        $ruta = CashReport::generate();

        try {
            $salida = Process::run([
                'python3',
                '-c',
                'from openpyxl import load_workbook; import sys; '
                . 'ws = load_workbook(sys.argv[1])["Resumen"]; '
                . 'print("|".join(str(r[0]) for r in ws.iter_rows(min_row=3, max_row=40, max_col=1, values_only=True) if r[0] and str(r[0])[:2] == "20"))',
                $ruta,
            ]);

            $this->assertTrue($salida->successful(), $salida->errorOutput());

            $meses = array_values(array_filter(explode('|', trim($salida->output()))));

            $this->assertContains('2020-12', $meses, 'Meses: ' . implode(', ', $meses));
            $this->assertContains(now()->format('Y-m'), $meses, 'Meses: ' . implode(', ', $meses));

            // Lo que no puede aparecer es un mes sin movimiento (el relleno de ceros).
            $this->assertNotContains('2021-01', $meses, 'Meses: ' . implode(', ', $meses));
            $this->assertNotContains('2023-06', $meses, 'Meses: ' . implode(', ', $meses));
            $this->assertNotContains('2025-12', $meses, 'Meses: ' . implode(', ', $meses));
        } finally {
            File::delete($ruta);
        }
    }
}
