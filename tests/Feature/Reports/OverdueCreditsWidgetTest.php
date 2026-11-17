<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\Widgets\OverdueCreditsWidget;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Installment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La tabla de créditos vencidos: lo vencido por crédito, con el atraso a la vista.
 */
class OverdueCreditsWidgetTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    /**
     * Un crédito con dos cuotas vencidas y una al día.
     */
    private function creditoVencido(array $atributos = []): Credit
    {
        // La base de pruebas ya trae créditos vencidos: se limpian dentro de la
        // transacción para que la tabla tenga sólo el caso de la prueba.
        Credit::query()->delete();

        $cliente = Customer::factory()->create();
        $credito = Credit::factory()->create(['customer_id' => $cliente->id] + $atributos);

        $credito->installments()->create([
            'number' => 1,
            'amount' => 100,
            'remaining_balance' => 100,
            'due_date' => today()->subDays(45)->toDateString(),
            'status' => 'pending',
        ]);

        $credito->installments()->create([
            'number' => 2,
            'amount' => 50,
            'remaining_balance' => 50,
            'due_date' => today()->subDays(5)->toDateString(),
            'status' => 'late',
        ]);

        $credito->installments()->create([
            'number' => 3,
            'amount' => 70,
            'remaining_balance' => 70,
            'due_date' => today()->addDays(10)->toDateString(),
            'status' => 'pending',
        ]);

        return $credito;
    }

    public function test_muestra_lo_vencido_y_no_el_saldo_del_credito(): void
    {
        $credito = $this->creditoVencido();

        $registro = Livewire::test(OverdueCreditsWidget::class)
            ->assertOk()
            ->instance()
            ->getTableRecords()
            ->firstWhere('id', $credito->id);

        // Sólo las dos vencidas suman: 100 + 50; la tercera no está vencida.
        $this->assertSame(150.0, (float) $registro->overdue_amount);
        $this->assertSame(2, (int) $registro->overdue_count);
    }

    public function test_el_atraso_se_cuenta_desde_la_cuota_mas_vieja(): void
    {
        $credito = $this->creditoVencido();

        $registro = Livewire::test(OverdueCreditsWidget::class)
            ->instance()
            ->getTableRecords()
            ->firstWhere('id', $credito->id);

        $this->assertSame(45, (int) \Illuminate\Support\Carbon::parse($registro->oldest_due_date)
            ->startOfDay()
            ->diffInDays(today(), true));
    }

    public function test_sin_vencidos_muestra_el_estado_vacio(): void
    {
        Credit::query()->delete();

        Livewire::test(OverdueCreditsWidget::class)
            ->assertOk()
            ->assertSee(__('ui.widgets.no_overdue'));
    }

    public function test_el_resumen_dice_cuantos_y_cuanto(): void
    {
        $this->creditoVencido();

        $widget = Livewire::test(OverdueCreditsWidget::class)->assertOk()->instance();
        $resumen = $widget->getSubheading();

        $this->assertStringContainsString('1 crédito', $resumen);
        $this->assertStringContainsString('150.00', $resumen);
    }
}
