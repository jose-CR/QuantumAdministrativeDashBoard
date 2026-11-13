<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Models\Customer;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lo cobrado arranca vacío: un 0 por defecto hacía pensar que la entrada ya venía
 * con algo cobrado.
 */
class InflowPaidFieldTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_lo_cobrado_arranca_vacio(): void
    {
        $componente = Livewire::test(CreateInflow::class);

        $this->assertEmpty($componente->get('data.amount_paid'));
    }

    public function test_escribir_el_monto_no_llena_lo_cobrado(): void
    {
        $componente = Livewire::test(CreateInflow::class)
            ->fillForm(['amount' => 300]);

        $this->assertEmpty($componente->get('data.amount_paid'));
    }

    public function test_sin_cobrado_la_entrada_queda_pendiente(): void
    {
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm([
                'invoice_number' => 'INV-SIN-COBRO-' . uniqid(),
                'customer_id' => $cliente->id,
                'date' => now()->toDateString(),
                'salesperson' => 'Prueba Cobrado',
                'amount' => 300,
                'payment_method' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('salesperson', 'Prueba Cobrado')->latest('id')->firstOrFail();

        $this->assertSame('pending', $entrada->payment_status);
        $this->assertSame('0.00', $entrada->amount_paid);
        $this->assertSame(300.0, $entrada->receivable);
    }
}
