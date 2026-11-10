<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\ArticlesStatsWidget;
use App\Filament\Admin\Widgets\CashFlowStatsWidget;
use App\Filament\Admin\Widgets\CreditsStatsWidget;
use App\Filament\Admin\Widgets\CustomersStatsWidget;
use App\Filament\Admin\Widgets\OverdueCreditsWidget;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El tablero: antes existía vacío, con los widgets escritos pero sin registrar.
 */
class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_el_tablero_registra_los_cinco_widgets(): void
    {
        $widgets = (new Dashboard)->getWidgets();

        foreach ([
            CashFlowStatsWidget::class,
            CreditsStatsWidget::class,
            CustomersStatsWidget::class,
            ArticlesStatsWidget::class,
            OverdueCreditsWidget::class,
        ] as $widget) {
            $this->assertContains($widget, $widgets);
        }
    }

    public function test_el_tablero_se_ve_con_sus_cifras(): void
    {
        Inflow::factory()->create(['amount' => 1000, 'amount_paid' => 400]);

        // Los widgets son componentes Livewire aparte: el tablero se prueba que
        // responda y cada widget que muestre sus cifras.
        Livewire::test(Dashboard::class)->assertOk();

        Livewire::test(CashFlowStatsWidget::class)
            ->assertOk()
            ->assertSee('Entradas')
            ->assertSee('Cobrado')
            ->assertSee('Por cobrar');
    }

    public function test_los_widgets_funcionan_sin_filtros_de_fecha(): void
    {
        // Sin filtros, pageFilters es null: los widgets no deben romperse.
        Livewire::test(CashFlowStatsWidget::class)->assertOk();
        Livewire::test(CreditsStatsWidget::class)->assertOk();
        Livewire::test(CustomersStatsWidget::class)->assertOk();
        Livewire::test(ArticlesStatsWidget::class)->assertOk();
    }
}
