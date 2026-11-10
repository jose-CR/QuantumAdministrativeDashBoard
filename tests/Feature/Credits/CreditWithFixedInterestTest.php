<?php

namespace Tests\Feature\Credits;

use App\Filament\Admin\Resources\Credits\Loans\Pages\CreateLoans;
use App\Models\Article;
use App\Models\Category;
use App\Models\Credit;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dos clientes pueden llevarse el mismo bien al mismo plazo y pagar distinto si
 * el interés se pactó: la cuota y el total salen de la tasa, no del formulario.
 */
class CreditWithFixedInterestTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_un_credito_con_interes_fijo_se_cobra_con_la_tasa_pactada(): void
    {
        $articulo = Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Batería de litio 60V',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 500,
            'stock' => 5,
        ]);

        Livewire::test(CreateLoans::class)
            ->fillForm([
                'customer_id' => Customer::factory()->create()->id,
                'items' => [
                    [
                        'item_type' => Article::class,
                        'item_id' => $articulo->id,
                        'price' => 500,
                        'quantity' => 2,
                    ],
                ],
                'initial_amount' => 1000,
                'down_payment' => 0,
                'installments' => 3,
                'interest_type' => 'fixed',
                'interest_rate' => 5,
                // La cuota que haya en el formulario se ignora: manda la tasa.
                'installment_amount' => 1,
                'periodicity' => 'monthly',
                'start_date' => today()->toDateString(),
                'payment_day' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $credito = Credit::query()->latest('id')->firstOrFail();

        $this->assertSame('fixed', $credito->interest_type);
        $this->assertSame('5.00', $credito->interest_rate);
        $this->assertSame('1000.00', $credito->financed_amount);
        $this->assertSame('150.00', $credito->total_interest);
        $this->assertSame('1150.00', $credito->total_amount);
        $this->assertSame('383.33', $credito->installment_amount);

        // Las cuotas suman exactamente el total: la última absorbe el redondeo.
        $this->assertSame(
            1150.0,
            round((float) $credito->installments()->sum('amount'), 2)
        );
        $this->assertSame(
            '383.34',
            $credito->installments()->orderByDesc('number')->firstOrFail()->amount
        );
        $this->assertSame('1150.00', $credito->pending_balance);
    }

    public function test_sin_interes_fijo_sigue_mandando_la_cuota(): void
    {
        $articulo = Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casco abatible',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 500,
            'stock' => 5,
        ]);

        Livewire::test(CreateLoans::class)
            ->fillForm([
                'customer_id' => Customer::factory()->create()->id,
                'items' => [
                    [
                        'item_type' => Article::class,
                        'item_id' => $articulo->id,
                        'price' => 500,
                        'quantity' => 2,
                    ],
                ],
                'initial_amount' => 1000,
                'down_payment' => 0,
                'installments' => 4,
                'interest_type' => 'auto',
                'interest_rate' => null,
                'installment_amount' => 300,
                'periodicity' => 'monthly',
                'start_date' => today()->toDateString(),
                'payment_day' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $credito = Credit::query()->latest('id')->firstOrFail();

        $this->assertSame('auto', $credito->interest_type);
        $this->assertSame('1200.00', $credito->total_amount);
        $this->assertSame('200.00', $credito->total_interest);
        // (200 / 4) / 1000 * 100 = 5% por cuota.
        $this->assertSame('5.00', $credito->interest_rate);
    }
}
