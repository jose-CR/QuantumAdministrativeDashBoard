<?php

namespace Tests\Unit\Credits;

use App\Models\Alert;
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

    public function test_refinanciar_conserva_la_tasa_pactada(): void
    {
        $cliente = Customer::factory()->create();

        $credito = $this->makeCredit([['amount' => 100], ['amount' => 100]], $cliente);
        $credito->update(['interest_type' => 'fixed', 'interest_rate' => 3]);

        $nuevo = app(RefinancingService::class)->execute($cliente, $credito, [
            'installments' => 4,
            'installment_amount' => 50,
            'periodicity' => 'monthly',
            'start_date' => today()->addMonth()->toDateString(),
            'payment_day' => 5,
        ]);

        $this->assertSame('fixed', $nuevo->interest_type);
        $this->assertSame('3.00', $nuevo->interest_rate);

        // 200 financiados al 3% por cuota en 4 cuotas = 224
        $this->assertSame('224.00', $nuevo->total_amount);
        $this->assertSame('56.00', $nuevo->installment_amount);
    }

    public function test_el_credito_nuevo_es_por_el_saldo_pendiente_y_no_arrastra_la_prima(): void
    {
        $cliente = Customer::factory()->create();

        $credito = $this->makeCredit([['amount' => 250], ['amount' => 250]], $cliente);
        $credito->update([
            'initial_amount' => 1000,
            'down_payment' => 200,
            'financed_amount' => 800,
            'pending_balance' => 500,
        ]);

        $nuevo = app(RefinancingService::class)->execute($cliente, $credito, [
            'installments' => 5,
            'installment_amount' => 100,
            'periodicity' => 'monthly',
            'start_date' => today()->addMonth()->toDateString(),
            'payment_day' => 5,
        ]);

        // Se refina lo que se debe, no el precio original con la prima otra vez.
        $this->assertSame('500.00', $nuevo->initial_amount);
        $this->assertSame('0.00', $nuevo->down_payment);
        $this->assertSame('500.00', $nuevo->financed_amount);
        $this->assertSame('500.00', $nuevo->total_amount);
    }

    public function test_refinanciar_cancela_las_alertas_de_las_cuotas_que_quedan(): void
    {
        $cliente = Customer::factory()->create();
        $credito = $this->makeCredit([['amount' => 100]], $cliente);
        $cuota = $credito->installments()->firstOrFail();

        $alerta = Alert::query()->create([
            'customer_id' => $cliente->id,
            'credit_id' => $credito->id,
            'installment_id' => $cuota->id,
            'type' => Alert::TYPE_OVERDUE,
            'title' => 'Cuota vencida',
            'message' => 'Cuota vencida de prueba',
            'alert_at' => now(),
            'status' => Alert::STATUS_PENDING,
        ]);

        app(RefinancingService::class)->execute($cliente, $credito, [
            'installments' => 2,
            'installment_amount' => 50,
            'periodicity' => 'monthly',
            'start_date' => today()->addMonth()->toDateString(),
            'payment_day' => 5,
        ]);

        $this->assertSame('cancelled', $alerta->fresh()->status);
    }

    public function test_borrar_el_credito_refinanciado_no_devuelve_el_stock(): void
    {
        [$viejo, , $articulo] = $this->refinance();

        $this->assertSame(8, $articulo->fresh()->stock);

        $viejo->delete();

        // Los bienes viven en el crédito nuevo: borrar el viejo no devuelve nada.
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
