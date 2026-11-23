<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\Widgets\CashInvoicesWidget;
use App\Models\Customer;
use App\Models\Inflow;
use App\Support\Cash\CashInvoices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El widget compacto de caja: dos filas con la cifra y un modal con las facturas.
 */
class CashInvoicesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        Inflow::query()->delete();
    }

    private function factura(string $numero, float $monto, float $cobrado, string $fecha): Inflow
    {
        return Inflow::factory()->create([
            'customer_id' => Customer::factory()->create(['full_name' => 'Cliente ' . $numero])->id,
            'invoice_number' => $numero,
            'amount' => $monto,
            'amount_paid' => $cobrado,
            'date' => $fecha,
            'salesperson' => 'Sin vendedor',
            // El estado se deriva del cobrado; si llega "paid" con cobro 0 el modelo lo
            // completa al total.
            'payment_status' => 'pending',
        ]);
    }

    public function test_resume_lo_cobrado_y_lo_que_falta(): void
    {
        $this->factura('F-001', 100, 100, '2026-10-05');
        $this->factura('F-002', 200, 50, '2026-10-06');
        $this->factura('F-003', 300, 0, '2026-10-07');

        $resumen = app(CashInvoices::class)->summary('2026-10-01', '2026-10-31');

        // Cobrado: la pagada completa y el abono de la parcial.
        $this->assertSame(2, $resumen['collected']['count']);
        $this->assertSame(150.0, $resumen['collected']['amount']);

        // Por cobrar: la parcial (150) y la que no tiene nada (300).
        $this->assertSame(2, $resumen['pending']['count']);
        $this->assertSame(450.0, $resumen['pending']['amount']);
    }

    public function test_el_detalle_trae_las_facturas_con_su_saldo(): void
    {
        $this->factura('F-101', 200, 50, '2026-10-06');

        $resumen = app(CashInvoices::class)->summary('2026-10-01', '2026-10-31');

        $factura = $resumen['pending']['invoices'][0];

        $this->assertSame('F-101', $factura['invoice']);
        $this->assertSame('Cliente F-101', $factura['customer']);
        $this->assertSame(200.0, $factura['amount']);
        $this->assertSame(50.0, $factura['paid']);
        $this->assertSame(150.0, $factura['balance']);
    }

    public function test_respeta_el_rango_de_fechas(): void
    {
        $this->factura('F-201', 100, 0, '2026-10-10');
        $this->factura('F-202', 100, 0, '2026-11-10');

        $resumen = app(CashInvoices::class)->summary('2026-10-01', '2026-10-31');

        $this->assertSame(['F-201'], array_column($resumen['pending']['invoices'], 'invoice'));
    }

    public function test_el_widget_muestra_las_dos_filas(): void
    {
        $this->factura('F-301', 90, 90, '2026-10-10');

        Livewire::test(CashInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])
            ->assertOk()
            ->assertSee(__('ui.widgets.cash_invoices'))
            ->assertSee(__('ui.widgets.collected'))
            ->assertSee(__('ui.widgets.receivable'))
            ->assertDontSee('F-301'); // el detalle vive en el modal
    }

    public function test_el_modal_lista_las_facturas(): void
    {
        $this->factura('F-401', 500, 500, '2026-10-10');

        Livewire::test(CashInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])
            // Monta sin error: la fila sabe qué modal abrir.
            ->mountTableAction('facturas', 'collected');

        // El cuerpo del modal es la vista: se comprueba lo que muestra.
        $resumen = app(CashInvoices::class)->summary('2026-10-01', '2026-10-31');

        $html = view('filament.modals.invoice-list', [
            'facturas' => $resumen['collected']['invoices'],
        ])->render();

        $this->assertStringContainsString('F-401', $html);
        $this->assertStringContainsString('Cliente F-401', $html);
        $this->assertStringContainsString('500.00', $html);
    }

    public function test_sin_facturas_lo_dice(): void
    {
        Livewire::test(CashInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2020-01-01', 'endDate' => '2020-01-31'],
        ])
            ->assertOk()
            ->assertSee(__('ui.widgets.cash_invoices'));
    }
}
