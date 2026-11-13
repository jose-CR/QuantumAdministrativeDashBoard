<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Models\Customer;
use App\Models\Inflow;
use App\Support\Salespeople;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El vendedor se elige de una lista con los que ya se usaron y se puede crear uno
 * nuevo desde el mismo select.
 */
class SalespersonSelectTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function datos(Customer $cliente, mixed $vendedor = null): array
    {
        return [
            'invoice_number' => 'INV-VENDEDOR-' . uniqid(),
            'customer_id' => $cliente->id,
            'date' => now()->toDateString(),
            'salesperson' => $vendedor,
            'amount' => 100,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ];
    }

    public function test_la_lista_trae_los_vendedores_ya_usados(): void
    {
        Inflow::factory()->create(['salesperson' => 'María López']);
        Inflow::factory()->create(['salesperson' => 'José Pérez']);
        Inflow::factory()->create(['salesperson' => 'José Pérez']);

        $vendedores = Salespeople::all();

        // La base de pruebas ya trae entradas sembradas: se comprueba que estén los
        // dos vendedores y que el repetido aparezca una sola vez.
        $this->assertArrayHasKey('José Pérez', $vendedores);
        $this->assertArrayHasKey('María López', $vendedores);
        $this->assertSame(1, collect(array_keys($vendedores))->filter(fn (string $nombre): bool => $nombre === 'José Pérez')->count());
        $this->assertSame(array_keys($vendedores), collect(array_keys($vendedores))->sort()->values()->all());
    }

    public function test_el_buscador_filtra_por_lo_que_se_escribe(): void
    {
        Inflow::factory()->create(['salesperson' => 'María López']);
        Inflow::factory()->create(['salesperson' => 'José Pérez']);

        $this->assertArrayHasKey('María López', Salespeople::search('marí'));
        $this->assertArrayHasKey('José Pérez', Salespeople::search('JOSÉ'));
        $this->assertArrayNotHasKey('José Pérez', Salespeople::search('marí'));
    }

    public function test_el_select_guarda_un_vendedor_nuevo(): void
    {
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente, 'Ana Nueva'))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Ana Nueva', Inflow::query()->latest('id')->firstOrFail()->salesperson);
        $this->assertArrayHasKey('Ana Nueva', Salespeople::all());
    }

    public function test_un_vendedor_existente_no_se_duplica_por_mayusculas(): void
    {
        Inflow::factory()->create(['salesperson' => 'José Pérez']);

        $this->assertSame('José Pérez', Salespeople::normalize('  josé pérez '));
        $this->assertSame('Marta Ruiz', Salespeople::normalize('  Marta   Ruiz '));
        $this->assertNull(Salespeople::normalize('   '));
    }
}
