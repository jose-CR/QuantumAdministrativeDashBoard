<?php

namespace Tests\Feature\Credits;

use App\Filament\Admin\Resources\Credits\Loans\Pages\CreateLoans;
use App\Models\Article;
use App\Models\Category;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Un crédito puede llevar artículos por cantidad desde el panel: el repetidor
 * de bienes acepta la opción "Artículo", pide la cantidad y descuenta el stock.
 */
class CreditWithStockArticleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_un_credito_del_panel_puede_llevar_un_articulo_por_cantidad(): void
    {
        $articulo = Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casco abatible',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 25,
            'stock' => 10,
        ]);

        $cliente = Customer::factory()->create();

        Livewire::test(CreateLoans::class)
            ->fillForm([
                'customer_id' => $cliente->id,
                'items' => [
                    [
                        'item_type' => Article::class,
                        'item_id' => $articulo->id,
                        'price' => 25,
                        'quantity' => 2,
                    ],
                ],
                'initial_amount' => 50,
                'down_payment' => 0,
                'installments' => 1,
                'interest_type' => 'auto',
                'installment_amount' => 50,
                'periodicity' => 'monthly',
                'start_date' => today()->toDateString(),
                'payment_day' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $credito = Credit::query()->latest('id')->firstOrFail();
        $item = $credito->items()->firstOrFail();

        $this->assertSame(Article::class, $item->item_type);
        $this->assertSame($articulo->id, $item->item_id);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('2 × Casco abatible', $item->display_name);
        $this->assertSame(8, $articulo->fresh()->stock);
        $this->assertSame('50.00', $credito->initial_amount);
    }
}
