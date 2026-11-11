<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\ListOutflows;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las dos pantallas de caja: entradas con cobrado y saldo, y salidas.
 */
class CashScreensTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_las_pantallas_de_caja_responden(): void
    {
        Livewire::test(ListInflows::class)->assertOk();
        Livewire::test(ListOutflows::class)->assertOk();
    }

    public function test_la_entrada_parcial_se_ve_con_su_saldo(): void
    {
        $entrada = Inflow::factory()->create([
            'amount' => 500,
            'amount_paid' => 200,
        ]);

        $this->assertSame('partial', $entrada->fresh()->payment_status);
        $this->assertSame(300.0, $entrada->fresh()->receivable);

        // La tabla se carga después del primer render (deferred), así que acá se
        // verifica la pantalla y las columnas nuevas; los números van en las
        // pruebas del modelo.
        Livewire::test(ListInflows::class)
            ->assertOk()
            ->assertSee('Cobrado')
            ->assertSee('Por cobrar');
    }

    public function test_el_filtro_de_parciales_sin_cobro_encuentra_los_viejos(): void
    {
        $sinCobro = Inflow::factory()->create([
            'amount' => 100,
            'amount_paid' => 0,
            'payment_status' => 'pending',
        ]);

        // Se fuerza el estado viejo: parcial con cobrado 0 (dato histórico).
        \Illuminate\Support\Facades\DB::table('inflows')
            ->where('id', $sinCobro->id)
            ->update(['payment_status' => 'partial']);

        $encontrados = Inflow::query()
            ->where('payment_status', 'partial')
            ->where('amount_paid', '<=', 0)
            ->pluck('id');

        $this->assertTrue($encontrados->contains($sinCobro->id));
    }
}
