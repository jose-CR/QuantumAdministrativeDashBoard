<?php

namespace Tests\Unit\Credits;

use App\Filament\Admin\Widgets\CreditsCollectedByTagWidget;
use App\Filament\Admin\Widgets\CreditsDueByTagWidget;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Tag;
use App\Support\Credits\CreditsMonthSummary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Los dos informes del tablero, separados: lo que vence y lo que se cobró.
 */
class CreditsMonthSummaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        // La base de pruebas trae datos sembrados: se limpia dentro de la transacción.
        Credit::query()->delete();
        Inflow::query()->delete();
    }

    private function clienteConCuota(string $nombre, float $monto, string $vence, array $etiquetas = []): Customer
    {
        $cliente = Customer::factory()->create(['full_name' => $nombre]);

        foreach ($etiquetas as $etiqueta) {
            $cliente->attachTag(Tag::findOrCreate($etiqueta, 'customer'));
        }

        $credito = Credit::factory()->create([
            'customer_id' => $cliente->id,
            'status' => 'active',
        ]);

        $credito->installments()->create([
            'number' => 1,
            'amount' => $monto,
            'remaining_balance' => $monto,
            'due_date' => $vence,
            'status' => 'pending',
        ]);

        return $cliente;
    }

    private function cobro(Customer $cliente, float $monto, string $fecha): void
    {
        Inflow::factory()->create([
            'customer_id' => $cliente->id,
            'amount' => $monto,
            'amount_paid' => $monto,
            'date' => $fecha,
            'invoice_number' => 'INV-' . uniqid(),
        ]);
    }

    public function test_los_vencimientos_del_rango_por_etiqueta(): void
    {
        $this->clienteConCuota('Cliente Ventas', 100, '2026-10-10', ['Crédito de ventas']);
        $this->clienteConCuota('Cliente Empresa', 200, '2026-10-20', ['Empresa']);

        // Fuera del rango.
        $this->clienteConCuota('Cliente Noviembre', 999, '2026-11-10', ['Crédito de ventas']);

        // Un crédito cancelado no vence.
        $cancelado = Customer::factory()->create();
        $creditoCancelado = Credit::factory()->create(['customer_id' => $cancelado->id, 'status' => 'cancelled']);
        $creditoCancelado->installments()->create([
            'number' => 1, 'amount' => 500, 'remaining_balance' => 0,
            'due_date' => '2026-10-15', 'status' => 'cancelled',
        ]);

        $filas = collect(app(CreditsMonthSummary::class)->due('2026-10-01', '2026-10-31'))
            ->keyBy('tag');

        $this->assertSame(100.0, $filas['Crédito de ventas']['amount']);
        $this->assertSame(200.0, $filas['Empresa']['amount']);
        $this->assertSame(300.0, $filas->firstWhere('is_total', true)['amount']);
    }

    public function test_la_cobranza_del_rango_solo_de_clientes_con_credito(): void
    {
        $conCredito = $this->clienteConCuota('Cliente Con Crédito', 100, '2026-10-10', ['Crédito de ventas']);
        $sinCredito = Customer::factory()->create(['full_name' => 'Cliente De Contado']);

        $this->cobro($conCredito, 60, '2026-10-05');
        $this->cobro($conCredito, 500, '2026-09-30'); // fuera del rango
        $this->cobro($sinCredito, 999, '2026-10-07'); // no tiene crédito

        $filas = collect(app(CreditsMonthSummary::class)->collected('2026-10-01', '2026-10-31'))
            ->keyBy('tag');

        $this->assertSame(60.0, $filas['Crédito de ventas']['amount']);
        $this->assertSame(60.0, $filas->firstWhere('is_total', true)['amount']);
    }

    public function test_el_total_no_cuenta_dos_veces_al_cliente_con_dos_etiquetas(): void
    {
        $cliente = $this->clienteConCuota('Cliente Con Dos', 300, '2026-10-10', [
            'Crédito de ventas',
            'Empresa',
        ]);

        $this->cobro($cliente, 100, '2026-10-10');

        $vencimientos = collect(app(CreditsMonthSummary::class)->due('2026-10-01', '2026-10-31'));

        $this->assertSame(300.0, $vencimientos->firstWhere('tag', 'Crédito de ventas')['amount']);
        $this->assertSame(300.0, $vencimientos->firstWhere('tag', 'Empresa')['amount']);
        $this->assertSame(300.0, $vencimientos->firstWhere('is_total', true)['amount']);
    }

    public function test_los_clientes_sin_etiqueta_tienen_su_fila(): void
    {
        $this->clienteConCuota('Cliente Suelto', 80, '2026-10-15');

        $suelto = collect(app(CreditsMonthSummary::class)->due('2026-10-01', '2026-10-31'))
            ->firstWhere('tag', __('ui.widgets.no_tag'));

        $this->assertSame(80.0, $suelto['amount']);
    }

    public function test_sin_fechas_usa_el_mes_actual(): void
    {
        $this->clienteConCuota('Cliente De Hoy', 50, now()->startOfMonth()->addDays(3)->toDateString());

        $total = collect(app(CreditsMonthSummary::class)->due())->firstWhere('is_total', true);

        $this->assertSame(50.0, $total['amount']);
    }

    public function test_cada_fila_trae_el_detalle_de_sus_clientes(): void
    {
        $this->clienteConCuota('Ana Detalle', 100, '2026-10-10', ['Crédito de ventas']);
        $this->clienteConCuota('Beto Detalle', 50, '2026-10-12', ['Crédito de ventas']);

        $fila = collect(app(CreditsMonthSummary::class)->due('2026-10-01', '2026-10-31'))
            ->firstWhere('tag', 'Crédito de ventas');

        // Ordenado de mayor a menor.
        $this->assertSame(['Ana Detalle', 'Beto Detalle'], array_column($fila['customers'], 'name'));
        $this->assertSame([100.0, 50.0], array_column($fila['customers'], 'amount'));
    }

    public function test_la_cobranza_filtra_por_vendedor(): void
    {
        $cliente = $this->clienteConCuota('Cliente Vendedor', 100, '2026-10-10', ['Crédito de ventas']);

        Inflow::factory()->create([
            'customer_id' => $cliente->id, 'amount' => 60, 'amount_paid' => 60,
            'date' => '2026-10-05', 'salesperson' => 'Vendedor A', 'invoice_number' => 'INV-A',
        ]);
        Inflow::factory()->create([
            'customer_id' => $cliente->id, 'amount' => 40, 'amount_paid' => 40,
            'date' => '2026-10-06', 'salesperson' => 'Vendedor B', 'invoice_number' => 'INV-B',
        ]);

        $resumen = app(CreditsMonthSummary::class);

        $this->assertSame(60.0, collect($resumen->collected('2026-10-01', '2026-10-31', 'Vendedor A'))
            ->firstWhere('is_total', true)['amount']);

        $this->assertSame(100.0, collect($resumen->collected('2026-10-01', '2026-10-31'))
            ->firstWhere('is_total', true)['amount']);
    }

    public function test_los_dos_widgets_se_renderizan_con_los_filtros_del_tablero(): void
    {
        $this->clienteConCuota('Cliente Visible', 120, '2026-10-10', ['Crédito de ventas']);

        $filtros = ['pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31']];

        Livewire::test(CreditsDueByTagWidget::class, $filtros)
            ->assertOk()
            // El título traducido, no el nombre de la clase.
            ->assertSee(__('ui.widgets.credits_due_by_tag'))
            ->assertDontSee('Credits Due By Tag')
            ->assertSee('Crédito de ventas')
            ->assertSee('Total');

        Livewire::test(CreditsCollectedByTagWidget::class, $filtros)
            ->assertOk()
            ->assertSee('Total');
    }

    public function test_los_widgets_se_renderizan_sin_filtros(): void
    {
        Livewire::test(CreditsDueByTagWidget::class)->assertOk();
        Livewire::test(CreditsCollectedByTagWidget::class)->assertOk();
    }
}
