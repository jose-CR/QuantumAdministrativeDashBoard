<?php

namespace Tests\Feature\Settings;

use App\Filament\Admin\Resources\Settings\DocumentTypes\Pages\ManageDocumentTypes;
use App\Models\DocumentType;
use App\Models\Inflow;
use App\Support\DocumentTypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El catálogo de tipos de documento: se administra desde el panel y lo que se agregue
 * aparece en Caja sin tocar código.
 */
class DocumentTypeCatalogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        DocumentTypes::flush();
    }

    public function test_el_catalogo_trae_los_cinco_de_siempre(): void
    {
        $codigos = DocumentTypes::all();

        foreach (['cf', 'ccf', 'excluded', 'receipt', 'invoice'] as $codigo) {
            $this->assertContains($codigo, $codigos);
        }
    }

    public function test_cada_tipo_dice_donde_aplica(): void
    {
        // «Factura de compra» no puede ofrecerse en una entrada de caja.
        $this->assertNotContains('invoice', DocumentTypes::inflow());
        $this->assertContains('invoice', DocumentTypes::outflow());

        // «Sujeto excluido» es una venta, no una compra.
        $this->assertContains('excluded', DocumentTypes::inflow());
        $this->assertNotContains('excluded', DocumentTypes::outflow());

        $this->assertContains('ccf', DocumentTypes::inflow());
        $this->assertContains('ccf', DocumentTypes::outflow());
    }

    public function test_se_agrega_un_tipo_desde_el_panel_y_aparece_en_caja(): void
    {
        Livewire::test(ManageDocumentTypes::class)
            ->callAction('create', [
                'code' => 'remision',
                'name' => 'Nota de remisión',
                'scope' => DocumentType::SCOPE_INFLOW,
                'sort' => 10,
                'active' => true,
            ])
            ->assertHasNoActionErrors();

        // Sin recompilar nada: el catálogo en memoria se suelta al guardar.
        $this->assertContains('remision', DocumentTypes::inflow());
        $this->assertSame('Nota de remisión', DocumentTypes::label('remision'));
        $this->assertArrayHasKey('remision', DocumentTypes::options(DocumentTypes::inflow()));
    }

    public function test_un_tipo_inactivo_no_se_ofrece_pero_el_documento_lo_conserva(): void
    {
        $tipo = DocumentType::query()->where('code', 'excluded')->firstOrFail();
        $tipo->update(['active' => false]);
        DocumentTypes::flush();

        $this->assertNotContains('excluded', DocumentTypes::inflow());
        // El nombre sigue resolviéndose para los documentos que ya lo tienen.
        $this->assertSame('Factura de sujeto excluido', DocumentTypes::label('excluded'));
    }

    public function test_no_se_borra_un_tipo_en_uso(): void
    {
        $entrada = Inflow::factory()->create([
            'customer_id' => \App\Models\Customer::factory()->create()->id,
            'salesperson' => 'Sin vendedor',
            'invoice_number' => 'INV-TIPO-' . uniqid(),
            'document_type' => 'ccf',
            'payment_status' => 'pending',
        ]);

        $tipo = DocumentType::query()->where('code', 'ccf')->firstOrFail();

        $this->assertSame(1, $tipo->usageCount());

        Livewire::test(ManageDocumentTypes::class)
            ->callTableAction('delete', $tipo);

        $this->assertDatabaseHas('document_types', ['id' => $tipo->id]);
        $this->assertSame('ccf', $entrada->fresh()->document_type);
    }

    public function test_el_codigo_se_guarda_en_minusculas(): void
    {
        $tipo = DocumentType::query()->create([
            'code' => '  REMISION ',
            'name' => 'Nota de remisión',
            'scope' => DocumentType::SCOPE_BOTH,
        ]);

        $this->assertSame('remision', $tipo->fresh()->code);
    }
}
