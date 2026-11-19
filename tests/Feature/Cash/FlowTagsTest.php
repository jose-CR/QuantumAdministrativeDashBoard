<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\ListOutflows;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Outflow;
use App\Models\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Etiquetas en Caja: una columna que muestra las del registro y un botón para
 * administrarlas, separadas por tipo (inflow / outflow).
 */
class FlowTagsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    private function entrada(): Inflow
    {
        return Inflow::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'salesperson' => 'Sin vendedor',
            'invoice_number' => 'INV-' . uniqid(),
        ]);
    }

    public function test_la_entrada_muestra_sus_etiquetas(): void
    {
        $entrada = $this->entrada();
        $entrada->attachTag('factura-electronica', 'inflow');

        // El listado pagina de 10 en 10: se mira el estado de la columna, que es lo
        // que dibuja la celda.
        Livewire::test(ListInflows::class)
            ->assertTableColumnStateSet('tags_inflow', 'factura-electronica', $entrada);
    }

    public function test_se_pueden_agregar_etiquetas_desde_la_tabla(): void
    {
        $entrada = $this->entrada();

        Livewire::test(ListInflows::class)
            ->callTableAction('etiquetas', $entrada, ['tags' => ['urgente', 'revisar']]);

        $this->assertEqualsCanonicalizing(
            ['urgente', 'revisar'],
            $entrada->fresh()->tagsWithType('inflow')->pluck('name')->all(),
        );
    }

    public function test_las_etiquetas_del_cliente_no_se_mezclan_con_las_de_la_entrada(): void
    {
        $cliente = Customer::factory()->create();
        $cliente->attachTag('mayorista', 'customer');

        $entrada = Inflow::factory()->create([
            'customer_id' => $cliente->id,
            'salesperson' => 'Sin vendedor',
            'invoice_number' => 'INV-' . uniqid(),
        ]);

        $entrada->attachTag('urgente', 'inflow');

        $this->assertSame(['urgente'], $entrada->fresh()->tagsWithType('inflow')->pluck('name')->all());
        $this->assertSame(['mayorista'], $cliente->fresh()->tagsWithType('customer')->pluck('name')->all());
    }

    public function test_la_salida_tambien_tiene_etiquetas(): void
    {
        $salida = Outflow::factory()->create();

        Livewire::test(ListOutflows::class)
            ->callTableAction('etiquetas', $salida, ['tags' => ['repuestos']])
            ->assertHasNoTableActionErrors();

        $this->assertSame(
            ['repuestos'],
            $salida->fresh()->tagsWithType('outflow')->pluck('name')->all(),
        );
        $this->assertSame('outflow', Tag::findFromString('repuestos', 'outflow')->type);
    }
}
