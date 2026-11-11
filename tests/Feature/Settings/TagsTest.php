<?php

namespace Tests\Feature\Settings;

use App\Filament\Admin\Resources\Tags\Pages\ManageTags;
use App\Models\Customer;
use App\Models\Tag;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Etiquetas de clientes: se crean desde Configuración, no se repiten y se ve
 * cuántos registros las usan.
 */
class TagsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_la_pantalla_de_etiquetas_responde(): void
    {
        Livewire::test(ManageTags::class)->assertOk();
    }

    public function test_no_se_puede_crear_la_misma_etiqueta_dos_veces(): void
    {
        Tag::findOrCreate('mayorista', 'customer');

        Livewire::test(ManageTags::class)
            ->callAction('create', [
                'name' => 'Mayorista',
                'type' => 'customer',
            ])
            ->assertHasActionErrors(['name']);

        $this->assertSame(1, Tag::query()->withType('customer')->count());
    }

    public function test_se_crea_una_etiqueta_nueva(): void
    {
        Livewire::test(ManageTags::class)
            ->callAction('create', [
                'name' => 'flota',
                'type' => 'customer',
            ])
            ->assertHasNoActionErrors();

        $etiqueta = Tag::query()->withType('customer')->firstOrFail();

        $this->assertSame('flota', $etiqueta->name);
    }

    public function test_borrar_la_etiqueta_limpia_sus_asignaciones(): void
    {
        $etiqueta = Tag::findOrCreate('vip', 'customer');

        $cliente = Customer::factory()->create();
        $cliente->attachTag($etiqueta);

        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('taggables')->where('tag_id', $etiqueta->id)->count());

        $etiqueta->delete();

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('taggables')->where('tag_id', $etiqueta->id)->count());
    }

    public function test_el_seeder_deja_las_etiquetas_base_y_no_duplica(): void
    {
        $this->seed(TagSeeder::class);
        $this->seed(TagSeeder::class);

        $this->assertSame(8, Tag::query()->withType('customer')->count());
    }
}
