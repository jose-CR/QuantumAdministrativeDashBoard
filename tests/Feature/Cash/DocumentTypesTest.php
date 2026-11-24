<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Filament\Admin\Resources\Administration\Inflows\Tables\InflowsTable;
use App\Models\Customer;
use App\Models\Inflow;
use App\Support\DocumentTypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tipo de documento en caja: lista cerrada (CF, CCF, sujeto excluido…), validada en la
 * base y usada por el formulario, la tabla, el filtro, el export y el import.
 */
class DocumentTypesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_las_entradas_viejas_sin_tipo_se_muestran_como_sin_especificar(): void
    {
        // No se les inventa un tipo fiscal a las filas que ya existían.
        $this->assertSame(__('ui.document_types.unspecified'), DocumentTypes::label(null));
    }

    public function test_normaliza_lo_que_viene_de_un_archivo(): void
    {
        // Los códigos ya no son constantes del código: salen del catálogo.
        $this->assertSame('ccf', DocumentTypes::cast('CCF'));
        $this->assertSame('ccf', DocumentTypes::cast('ccf'));
        $this->assertSame('ccf', DocumentTypes::cast('Comprobante de Crédito Fiscal'));
        $this->assertSame('cf', DocumentTypes::cast('Consumidor Final'));
        $this->assertSame('excluded', DocumentTypes::cast('sujeto excluido'));
        // Sin tilde también.
        $this->assertSame('ccf', DocumentTypes::cast('comprobante de credito fiscal'));
        $this->assertNull(DocumentTypes::cast('cualquier cosa'));
        $this->assertNull(DocumentTypes::cast(''));
    }

    public function test_la_entrada_guarda_el_tipo_de_documento(): void
    {
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm([
                'invoice_number' => 'INV-DOC-' . uniqid(),
                'document_type' => 'ccf',
                'customer_id' => $cliente->id,
                'date' => now()->toDateString(),
                'salesperson' => 'Sin vendedor',
                'amount' => 100,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('ccf', Inflow::query()->latest('id')->firstOrFail()->document_type);
    }

    public function test_un_tipo_inventado_no_entra_por_el_import(): void
    {
        // Sin CHECK en la base, la lista del catálogo es la que valida: lo que no
        // existe se descarta en vez de guardar un código que nadie sabe mostrar.
        $this->assertNull(DocumentTypes::cast('c.f.'));
        $this->assertNotContains('c.f.', DocumentTypes::accepted(DocumentTypes::inflow()));
    }

    public function test_el_filtro_de_la_tabla_usa_la_misma_lista(): void
    {
        $filtro = collect(InflowsTable::configure(\Filament\Tables\Table::make(new ListInflows))->getFilters())
            ->first(fn ($filtro): bool => $filtro->getName() === 'document_type');

        $this->assertNotNull($filtro, 'Falta el filtro de tipo de documento.');
        $this->assertSame(
            array_values(DocumentTypes::options(DocumentTypes::inflow())),
            array_values($filtro->getOptions()),
        );
    }

    public function test_el_export_y_el_import_lo_traen(): void
    {
        $columnasExport = collect(\App\Filament\Exports\Inflow\InflowExporter::getColumns())
            ->map(fn ($columna): string => $columna->getName());

        $this->assertContains('document_type', $columnasExport->all());

        $columnasImport = collect(\App\Filament\Imports\Inflow\InflowImporter::getColumns())
            ->map(fn ($columna): string => $columna->getName());

        $this->assertContains('document_type', $columnasImport->all());
    }
}
