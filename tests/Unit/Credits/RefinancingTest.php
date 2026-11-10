<?php

namespace Tests\Unit\Credits;

use App\Models\Article;
use App\Models\Category;
use App\Models\Customer;
use App\Services\Credits\RefinancingService;
use Exception;

/**
 * Refinanciar: el crédito viejo se cierra como 'refinanced', las cuotas que
 * quedaban se marcan igual, y el nuevo arranca con el saldo pendiente y los
 * mismos bienes.
 */
class RefinancingTest extends CreditTestCase
{
    private function refinance(array $extra = []): array
    {
        $cliente = Customer::factory()->create();

        $articulo = Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casco abatible',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 100,
            'stock' => 10,
        ]);

        $credito = $this->makeCredit([
            ['amount' => 100],
            ['amount' => 100],
        ], $cliente);

        $credito->items()->create([
            'item_type' => Article::class,
            'item_id' => $articulo->id,
            'price' => 100,
            'quantity' => 2,
        ]);

        $nuevo = app(RefinancingService::class)->execute($cliente, $credito, array_merge([
            'installments' => 4,
            'installment_amount' => 50,
            'periodicity' => 'monthly',
            'start_date' => today()->addMonth()->toDateString(),
            'payment_day' => 5,
        ], $extra));

        return [$credito, $nuevo, $articulo];
    }

    public function test_refinanciar_cierra_el_viejo_y_crea_el_nuevo(): void
    {
        [$viejo, $nuevo] = $this->refinance();

        $viejo->refresh();

        $this->assertSame('refinanced', $viejo->status);
        $this->assertSame('0.00', $viejo->pending_balance);
        $this->assertSame(
            ['refinanced', 'refinanced'],
            $viejo->installments()->orderBy('number')->pluck('status')->all()
        );

        $this->assertSame('active', $nuevo->status);
        $this->assertSame($viejo->id, $nuevo->refinanced_from_id);
        $this->assertSame(4, $nuevo->installments()->count());
        $this->assertSame(
            round((float) $nuevo->installments()->sum('remaining_balance'), 2),
            (float) $nuevo->pending_balance
        );
    }

    public function test_los_bienes_pasan_al_credito_nuevo_sin_mover_stock_otra_vez(): void
    {
        [$nuevo, $articulo] = array_slice($this->refinance(), 1);

        $this->assertSame(1, $nuevo->items()->count());

        $item = $nuevo->items()->firstOrFail();

        $this->assertSame(Article::class, $item->item_type);
        $this->assertSame($articulo->id, $item->item_id);
        $this->assertSame(2, $item->quantity);

        // Ya se habían descontado 2 al crear el crédito original: siguen siendo 8.
        $this->assertSame(8, $articulo->fresh()->stock);
    }

    public function test_no_se_puede_refinanciar_un_credito_cerrado(): void
    {
        $credito = $this->makeCredit([['amount' => 100]]);
        $credito->update(['status' => 'completed']);

        $this->expectException(Exception::class);

        app(RefinancingService::class)->execute(
            Customer::factory()->create(),
            $credito,
            [
                'installments' => 2,
                'installment_amount' => 50,
                'periodicity' => 'monthly',
                'start_date' => today()->toDateString(),
                'payment_day' => 5,
            ],
        );
    }
}
