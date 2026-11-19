<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inflow;
use App\Support\Salespeople;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El vendedor de una entrada es un EMPLEADO con código VEN-: la lista sale de la
 * plantilla, no de los nombres escritos a mano.
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

    private function vendedor(string $nombre, string $codigo): Employee
    {
        return Employee::query()->create([
            'code' => $codigo,
            'name' => $nombre,
            'job_title' => 'vendedor',
            'status' => Employee::STATUS_ACTIVE,
        ]);
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

    public function test_sin_codigos_de_vendedor_usa_el_puesto(): void
    {
        $this->vendedor('Ana Puesto', 'EMP-0100');

        // Nadie tiene VEN- todavía: no se deja el select vacío.
        $this->assertArrayHasKey('Ana Puesto', Salespeople::all());
        $this->assertStringStartsWith('VEN-', Salespeople::nextCode());
    }

    public function test_la_lista_trae_solo_a_los_empleados_con_codigo_de_vendedor(): void
    {
        $this->vendedor('María López', 'VEN-0001');
        $this->vendedor('José Pérez', 'VEN-0002');

        // Mecánico y bodeguero: no venden, no van en la lista.
        $this->vendedor('Carlos Mecánico', 'TEC-0001');
        $this->vendedor('Beto Bodega', 'BOD-0001');

        $vendedores = Salespeople::all();

        $this->assertArrayHasKey('María López', $vendedores);
        $this->assertArrayHasKey('José Pérez', $vendedores);
        $this->assertArrayNotHasKey('Carlos Mecánico', $vendedores);
        $this->assertArrayNotHasKey('Beto Bodega', $vendedores);

        // Alfabético.
        $this->assertSame(array_keys($vendedores), collect(array_keys($vendedores))->sort()->values()->all());
    }

    public function test_el_buscador_filtra_por_lo_que_se_escribe(): void
    {
        $this->vendedor('María López', 'VEN-0011');
        $this->vendedor('José Pérez', 'VEN-0012');

        $this->assertArrayHasKey('María López', Salespeople::search('marí'));
        $this->assertArrayHasKey('José Pérez', Salespeople::search('JOSÉ'));
        $this->assertArrayNotHasKey('José Pérez', Salespeople::search('marí'));
    }

    public function test_el_select_guarda_al_empleado_elegido(): void
    {
        $vendedor = $this->vendedor('María López', 'VEN-0030');
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente, $vendedor->name))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('María López', Inflow::query()->latest('id')->firstOrFail()->salesperson);
    }

    public function test_un_vendedor_nuevo_se_crea_como_empleado(): void
    {
        // Es lo que hace el botón "Nuevo vendedor" del select.
        $this->assertSame('Ana Nueva', Salespeople::create('Ana Nueva'));

        $empleado = Employee::query()->where('name', 'Ana Nueva')->firstOrFail();

        $this->assertStringStartsWith('VEN-', (string) $empleado->code);
        $this->assertSame('vendedor', $empleado->job_title);
        $this->assertArrayHasKey('Ana Nueva', Salespeople::all());
    }

    public function test_el_codigo_siguiente_no_repite(): void
    {
        $this->vendedor('Uno', 'VEN-0007');

        $this->assertSame('VEN-0008', Salespeople::nextCode());
    }

    public function test_un_empleado_se_encuentra_por_su_nombre(): void
    {
        $empleado = $this->vendedor('José Pérez', 'VEN-0020');

        $this->assertSame($empleado->id, Salespeople::employee('  josé PÉREZ ')['id'] ?? null);
        $this->assertNull(Salespeople::employee('Carlos Mecánico'));
    }
}
