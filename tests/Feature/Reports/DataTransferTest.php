<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\pages\DataTransfer;
use App\Models\Customer;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La pantalla de importar y exportar: todas las áreas y modelos en un lugar, y la
 * ruta específica que trae cada módulo.
 */
class DataTransferTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_la_pantalla_lista_las_areas_y_los_modelos(): void
    {
        $pagina = Livewire::test(DataTransfer::class);

        $pagina->assertOk()
            ->assertSee('Clientes')
            ->assertSee('Inventario')
            ->assertSee('Créditos')
            ->assertSee('Caja')
            ->assertSee('Administración');

        // Los diez modelos con su botón de exportar e importar.
        $pagina->assertSee('Artículos')
            ->assertSee('Unidades con serie')
            ->assertSee('Categorías')
            ->assertSee('Entradas')
            ->assertSee('Salidas')
            ->assertSee('Bancos')
            ->assertSee('Cuotas')
            ->assertSee('Pagos');
    }

    public function test_la_ruta_de_un_modelo_muestra_solo_su_tarjeta(): void
    {
        Livewire::test(DataTransfer::class, ['modelo' => 'inflows'])
            ->assertOk()
            ->assertSee('Entradas')
            ->assertDontSee('Unidades con serie')
            ->assertSee('Ver todas las áreas');
    }

    public function test_cada_modelo_del_catalogo_tiene_exportador_e_importador(): void
    {
        foreach (DataTransfer::registry() as $clave => $modelo) {
            $this->assertTrue(class_exists($modelo['exporter']), "Falta el exportador de {$clave}");
            $this->assertTrue(class_exists($modelo['importer']), "Falta el importador de {$clave}");
        }

        $this->assertCount(10, DataTransfer::registry());
    }

    public function test_la_accion_de_importar_abre_su_formulario(): void
    {
        $componente = Livewire::test(DataTransfer::class, ['modelo' => 'customers'])
            ->mountAction(TestAction::make('import-customers')->schemaComponent('customers'));

        $this->assertSame('import-customers', $componente->instance()->getMountedAction()?->getName());
    }

    public function test_exportar_desde_la_pantalla_encola_el_archivo(): void
    {
        Queue::fake();

        Customer::factory()->create();

        Livewire::test(DataTransfer::class, ['modelo' => 'customers'])
            ->mountAction(TestAction::make('export-customers')->schemaComponent('customers'))
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $export = Export::query()->latest('id')->first();

        $this->assertNotNull($export, 'No se creó la exportación.');
        $this->assertSame(\App\Filament\Exports\CustomerExporter::class, $export->exporter);
        $this->assertGreaterThanOrEqual(1, (int) $export->total_rows);
    }
}
