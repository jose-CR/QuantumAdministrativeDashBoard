<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Filament\Admin\Resources\Administration\Inflows\Pages\EditInflow;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\CreateOutflow;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\EditOutflow;
use App\Models\Article;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Outflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Los formularios de caja (entradas y salidas) con la sección de artículos.
 *
 * Abrir la pantalla es la prueba que caza una clase sin importar (el error llega
 * al usuario como «No se encontró la clase ...\Section»).
 */
class CashFormsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function articulo(int $stock = 10): Article
    {
        return Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casco abatible',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 25,
            'stock' => $stock,
        ]);
    }

    public function test_los_cuatro_formularios_de_caja_abren(): void
    {
        $entrada = Inflow::factory()->create(['amount' => 100, 'amount_paid' => 50]);
        $salida = Outflow::factory()->create();

        Livewire::test(CreateInflow::class)->assertOk();
        Livewire::test(EditInflow::class, ['record' => $entrada->getRouteKey()])->assertOk();
        Livewire::test(CreateOutflow::class)->assertOk();
        Livewire::test(EditOutflow::class, ['record' => $salida->getRouteKey()])->assertOk();
    }

    public function test_una_entrada_con_articulos_descuenta_stock(): void
    {
        $articulo = $this->articulo(stock: 10);
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm([
                'invoice_number' => 'INV-VENTA-1',
                'customer_id' => $cliente->id,
                'date' => now()->toDateString(),
                'salesperson' => 'José',
                'amount' => 75,
                'amount_paid' => 75,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'items' => [
                    ['article_id' => $articulo->id, 'quantity' => 3, 'price' => 25],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(7, $articulo->fresh()->stock);

        $entrada = Inflow::query()->where('invoice_number', 'INV-VENTA-1')->firstOrFail();

        $this->assertSame(1, $entrada->items()->count());
    }

    public function test_una_salida_con_articulos_suma_stock(): void
    {
        $articulo = $this->articulo(stock: 2);

        Livewire::test(CreateOutflow::class)
            ->fillForm([
                'invoice_date' => now()->toDateString(),
                'company' => 'Proveedor S.A.',
                'invoice_code' => 'COMPRA-1',
                'quantity' => 5,
                'amount' => 75,
                'area' => 'Bodega',
                'items' => [
                    ['article_id' => $articulo->id, 'quantity' => 5, 'price' => 15],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(7, $articulo->fresh()->stock);
        $this->assertSame(1, Outflow::query()->latest('id')->first()->items()->count());
    }
}
