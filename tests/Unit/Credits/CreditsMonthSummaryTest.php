<?php

namespace Tests\Unit\Credits;

use App\Filament\Admin\Widgets\CreditsByTagWidget;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Tag;
use App\Support\Credits\CreditsMonthSummary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El resumen del mes por etiqueta: lo que vence y lo que se cobró.
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

    public function test_suma_las_cuotas_y_los_cobros_del_rango_por_etiqueta(): void
    {
        Tag::findOrCreate('Crédito de ventas', 'customer');
        Tag::findOrCreate('Crédito de ventas y empresa', 'customer');

        $ventas = $this->clienteConCuota('Cliente Ventas', 100, '2026-10-10', ['Crédito de ventas']);
        $empresa = $this->clienteConCuota('Cliente Empresa', 200, '2026-10-20', ['Crédito de ventas y empresa']);

        $this->cobro($ventas, 60, '2026-10-05');
        $this->cobro($empresa, 150, '2026-10-25');

        // Fuera del rango: ni cuota ni cobro cuentan.
        $this->clienteConCuota('Cliente Noviembre', 999, '2026-11-10', ['Crédito de ventas']);
        $this->cobro($ventas, 500, '2026-09-30');

        $filas = collect(app(CreditsMonthSummary::class)->rows('2026-10-01', '2026-10-31'))
            ->keyBy('tag');

        $this->assertSame(100.0, $filas['Crédito de ventas']['installments']);
        $this->assertSame(60.0, $filas['Crédito de ventas']['collected']);
        $this->assertSame(40.0, $filas['Crédito de ventas']['difference']);

        $this->assertSame(200.0, $filas['Crédito de ventas y empresa']['installments']);
        $this->assertSame(150.0, $filas['Crédito de ventas y empresa']['collected']);
    }

    public function test_el_total_no_cuenta_dos_veces_al_cliente_con_dos_etiquetas(): void
    {
        Tag::findOrCreate('Crédito de ventas', 'customer');
        Tag::findOrCreate('Crédito de ventas y empresa', 'customer');

        $cliente = $this->clienteConCuota('Cliente Con Dos', 300, '2026-10-10', [
            'Crédito de ventas',
            'Crédito de ventas y empresa',
        ]);

        $this->cobro($cliente, 100, '2026-10-10');

        $filas = collect(app(CreditsMonthSummary::class)->rows('2026-10-01', '2026-10-31'));

        // Aparece en las dos filas de etiqueta…
        $this->assertSame(300.0, $filas->firstWhere('tag', 'Crédito de ventas')['installments']);
        $this->assertSame(300.0, $filas->firstWhere('tag', 'Crédito de ventas y empresa')['installments']);

        // …pero el total lo cuenta una sola vez.
        $total = $filas->firstWhere('is_total', true);
        $this->assertSame(300.0, $total['installments']);
        $this->assertSame(100.0, $total['collected']);
    }

    public function test_los_clientes_sin_etiqueta_tienen_su_fila(): void
    {
        $this->clienteConCuota('Cliente Suelto', 80, '2026-10-15');

        $filas = collect(app(CreditsMonthSummary::class)->rows('2026-10-01', '2026-10-31'));

        $suelto = $filas->firstWhere('tag', __('ui.widgets.no_tag'));

        $this->assertNotNull($suelto);
        $this->assertSame(80.0, $suelto['installments']);
    }

    public function test_sin_fechas_usa_el_mes_actual(): void
    {
        $this->clienteConCuota('Cliente De Hoy', 50, now()->startOfMonth()->addDays(3)->toDateString());

        $filas = collect(app(CreditsMonthSummary::class)->rows());

        $this->assertSame(50.0, $filas->firstWhere('is_total', true)['installments']);
    }

    public function test_el_widget_muestra_la_tabla_con_el_rango_del_tablero(): void
    {
        Tag::findOrCreate('Crédito de ventas', 'customer');
        $this->clienteConCuota('Cliente Visible', 120, '2026-10-10', ['Crédito de ventas']);

        Livewire::test(CreditsByTagWidget::class, [
            'pageFilters' => ['startDate' => '2026-10-01', 'endDate' => '2026-10-31'],
        ])
            ->assertOk()
            ->assertSee('Crédito de ventas')
            ->assertSee('Total');
    }
}
