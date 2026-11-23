<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\Widgets\CollectedInvoicesWidget;
use App\Filament\Admin\Widgets\ReceivableInvoicesWidget;
use App\Models\Customer;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las dos tablas que explican los números «Cobrado» y «Por cobrar» del tablero: qué
 * facturas están cobradas y cuáles faltan.
 */
class InvoiceTablesTest extends TestCase
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
            // El estado se deriva del cobrado, pero si llega "paid" con cobro 0 el
            // modelo lo completa al total: hay que decirle la verdad al crearlo.
            'payment_status' => 'pending',
        ]);
    }

    public function test_muestra_las_facturas_cobradas(): void
    {
        $cobrada = $this->factura('F-001', 100, 100, '2026-10-05');
        $parcial = $this->factura('F-002', 200, 50, '2026-10-06');
        $sinCobro = $this->factura('F-003', 300, 0, '2026-10-07');

        Livewire::test(CollectedInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$cobrada, $parcial])
            ->assertCanNotSeeTableRecords([$sinCobro]);
    }

    public function test_muestra_las_facturas_por_cobrar_con_su_saldo(): void
    {
        $cobrada = $this->factura('F-101', 100, 100, '2026-10-05');
        $parcial = $this->factura('F-102', 200, 50, '2026-10-06');
        $sinCobro = $this->factura('F-103', 300, 0, '2026-10-07');

        Livewire::test(ReceivableInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$parcial, $sinCobro])
            ->assertCanNotSeeTableRecords([$cobrada]);

        $this->assertSame(150.0, (float) $parcial->receivable);
        $this->assertSame(300.0, (float) $sinCobro->receivable);
    }

    public function test_respetan_el_rango_de_fechas(): void
    {
        $enOctubre = $this->factura('F-201', 100, 0, '2026-10-10');
        $enNoviembre = $this->factura('F-202', 100, 0, '2026-11-10');

        Livewire::test(ReceivableInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])
            ->assertCanSeeTableRecords([$enOctubre])
            ->assertCanNotSeeTableRecords([$enNoviembre]);
    }

    public function test_sin_cobro_anotado_tambien_cuenta_como_por_cobrar(): void
    {
        $sinnada = Inflow::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'invoice_number' => 'F-301',
            'amount' => 90,
            'amount_paid' => null,
            'payment_status' => 'pending',
            'date' => '2026-10-10',
            'salesperson' => 'Sin vendedor',
        ]);

        Livewire::test(ReceivableInvoicesWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])->assertCanSeeTableRecords([$sinnada]);
    }

    public function test_los_titulos_salen_traducidos(): void
    {
        Livewire::test(CollectedInvoicesWidget::class)
            ->assertOk()
            ->assertSee(__('ui.widgets.invoices_collected'))
            ->assertDontSee('Collected Invoices Widget');
    }
}
