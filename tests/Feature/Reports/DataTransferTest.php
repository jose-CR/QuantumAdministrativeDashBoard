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
        Livewire::test(DataTransfer::class, ['model' => 'inflows'])
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
        $componente = Livewire::test(DataTransfer::class, ['model' => 'customers'])
            ->mountAction(TestAction::make('import-customers')->schemaComponent('customers'));

        $this->assertSame('import-customers', $componente->instance()->getMountedAction()?->getName());
    }

    public function test_cada_tarjeta_exporta_su_propio_modelo(): void
    {
        // El bug que vio el taller: todas las tarjetas montaban la acción de la
        // primera (artículos exportaba clientes) porque el nombre era export-0.
        $html = Livewire::test(DataTransfer::class)->html();

        foreach (array_keys(DataTransfer::registry()) as $clave) {
            $this->assertStringContainsString("mountAction('export-{$clave}'", $html, "La tarjeta {$clave} no monta su export.");
            $this->assertStringContainsString("mountAction('export-csv-{$clave}'", $html, "La tarjeta {$clave} no monta su CSV.");
        }

        $this->assertStringNotContainsString("mountAction('export-0'", $html);
    }

    public function test_el_export_de_creditos_trae_cliente_montos_cuotas_e_interes(): void
    {
        $columnas = collect(\App\Filament\Exports\Credits\LoansExporter::getColumns())
            ->map(fn ($columna): string => $columna->getName())
            ->all();

        foreach (['customer', 'items', 'initial_amount', 'down_payment', 'financed_amount', 'installments', 'installment_amount', 'interest_rate', 'total_interest', 'total_amount', 'pending_balance', 'status'] as $columna) {
            $this->assertContains($columna, $columnas, "Al export de créditos le falta {$columna}.");
        }
    }

    public function test_cada_tarjeta_ofrece_excel_y_csv(): void
    {
        $componente = Livewire::test(DataTransfer::class, ['model' => 'inflows']);

        $componente->assertOk()
            ->assertSee('Excel')
            ->assertSee('CSV')
            ->assertSee('Importar');

        // Los dos formatos son acciones distintas, además del import.
        $componente->mountAction(TestAction::make('export-csv-inflows')->schemaComponent('inflows'));

        $this->assertSame('export-csv-inflows', $componente->instance()->getMountedAction()?->getName());
    }

    public function test_el_csv_se_genera_con_su_exporter(): void
    {
        Queue::fake();

        Livewire::test(DataTransfer::class, ['model' => 'inflows'])
            ->mountAction(TestAction::make('export-csv-inflows')->schemaComponent('inflows'))
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $export = Export::query()->latest('id')->first();

        $this->assertNotNull($export, 'No se creó la exportación CSV.');
        $this->assertSame(\App\Filament\Exports\Inflow\InflowExporter::class, $export->exporter);
    }

    public function test_exportar_desde_la_pantalla_encola_el_archivo(): void
    {
        Queue::fake();

        Customer::factory()->create();

        Livewire::test(DataTransfer::class, ['model' => 'customers'])
            ->mountAction(TestAction::make('export-customers')->schemaComponent('customers'))
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $export = Export::query()->latest('id')->first();

        $this->assertNotNull($export, 'No se creó la exportación.');
        $this->assertSame(\App\Filament\Exports\CustomerExporter::class, $export->exporter);
        $this->assertGreaterThanOrEqual(1, (int) $export->total_rows);
    }
}
