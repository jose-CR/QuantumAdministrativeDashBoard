<?php

namespace Tests\Feature\Settings;

use App\Filament\Admin\Resources\Tags\Pages\ManageTags;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Tag;
use App\Support\TagTypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Configuración → Etiquetas: los tres tipos, el bloqueo del tipo en uso y el borrado
 * protegido (la FK de `taggables` es cascada y se lleva la clasificación).
 */
class TagTypesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_los_tres_tipos_tienen_etiqueta_en_los_dos_idiomas(): void
    {
        $this->assertSame(['customer', 'inflow', 'outflow'], TagTypes::all());
        $this->assertCount(3, TagTypes::options());

        foreach (TagTypes::all() as $tipo) {
            $this->assertNotSame('ui.tags.types.' . $tipo, TagTypes::label($tipo));
            $this->assertNotSame(
                TagTypes::label($tipo),
                __('ui.tags.types.' . $tipo, [], 'en'),
                'Falta la traducción al inglés de ' . $tipo,
            );
        }

        // Un tipo desconocido se muestra tal cual, nunca la clave cruda.
        $this->assertSame('otro', TagTypes::label('otro'));
        $this->assertSame(__('ui.tags.types.unknown'), TagTypes::label(null));
    }

    public function test_se_crea_una_etiqueta_de_ingresos_desde_la_pantalla(): void
    {
        Livewire::test(ManageTags::class)
            ->callAction('create', [
                'name' => 'factura-electronica',
                'type' => TagTypes::INFLOW,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(TagTypes::INFLOW, Tag::findFromString('factura-electronica', TagTypes::INFLOW)->type);
    }

    public function test_una_etiqueta_de_ingresos_se_puede_renombrar(): void
    {
        // Antes fallaba: el select solo ofrecía "customer" y el guardado no validaba.
        $tag = Tag::findOrCreate('urgente', TagTypes::INFLOW);

        Livewire::test(ManageTags::class)
            ->callTableAction('edit', $tag, ['name' => 'muy urgente'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('muy urgente', $tag->fresh()->name);
        $this->assertSame(TagTypes::INFLOW, $tag->fresh()->type);
    }

    public function test_el_tipo_se_bloquea_cuando_la_etiqueta_esta_en_uso(): void
    {
        $cliente = Customer::factory()->create();
        $cliente->attachTag(Tag::findOrCreate('mayorista', TagTypes::CUSTOMER));

        $tag = Tag::findFromString('mayorista', TagTypes::CUSTOMER);

        $this->assertSame(1, $tag->usageCount());
        $this->assertSame([$tag->id => 1], Tag::usageCounts());
    }

    public function test_no_se_puede_borrar_una_etiqueta_en_uso(): void
    {
        $entrada = Inflow::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'salesperson' => 'Sin vendedor',
            'invoice_number' => 'INV-TAG-' . uniqid(),
            'payment_status' => 'pending',
        ]);

        $entrada->attachTag(Tag::findOrCreate('urgente', TagTypes::INFLOW));
        $tag = Tag::findFromString('urgente', TagTypes::INFLOW);

        Livewire::test(ManageTags::class)
            ->callTableAction('delete', $tag);

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_una_etiqueta_sin_usos_se_borra(): void
    {
        $tag = Tag::findOrCreate('sin-uso', TagTypes::OUTFLOW);

        Livewire::test(ManageTags::class)
            ->callTableAction('delete', $tag);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_la_tabla_muestra_el_tipo_traducido(): void
    {
        $tag = Tag::findOrCreate('repuestos', TagTypes::OUTFLOW);

        Livewire::test(ManageTags::class)
            ->assertOk()
            // El badge pinta la etiqueta traducida; el estado crudo sigue siendo el código.
            ->assertSee(__('ui.tags.types.outflow'))
            ->assertTableColumnStateSet('type', TagTypes::OUTFLOW, $tag);
    }
}
