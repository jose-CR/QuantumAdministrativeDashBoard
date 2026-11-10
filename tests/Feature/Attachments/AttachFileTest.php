<?php

namespace Tests\Feature\Attachments;

use App\Filament\Admin\Resources\Client\Customers\Pages\ViewCustomer;
use App\Models\Attachment;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Installment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Subir un archivo desde la sección «Adjuntos» de la ficha del cliente con el
 * FileUpload de Filament: al cliente, a un crédito (la prima) o a una cuota.
 */
class AttachFileTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    /** La ficha del cliente, donde vive la sección de adjuntos. */
    private function ficha(Customer $cliente)
    {
        return Livewire::test(ViewCustomer::class, ['record' => $cliente->getRouteKey()]);
    }

    /** La acción de subir, montada desde la sección. */
    private function subir(Customer $cliente, array $datos)
    {
        return $this->ficha($cliente)
            ->mountAction(TestAction::make('attachFile')->schemaComponent('adjuntos'))
            ->setActionData($datos)
            ->callMountedAction();
    }

    public function test_la_seccion_de_adjuntos_muestra_el_boton_de_subir(): void
    {
        $cliente = Customer::factory()->create();

        $this->ficha($cliente)
            ->assertOk()
            ->assertSee('Adjuntos')
            ->assertSee('Adjuntar archivo');
    }

    public function test_la_prima_se_guarda_en_el_credito(): void
    {
        $cliente = Customer::factory()->create();
        $credito = Credit::factory()->create(['customer_id' => $cliente->id]);

        $this->subir($cliente, [
            'target' => 'credit',
            'credit_id' => $credito->id,
            'files' => [UploadedFile::fake()->image('prima.jpg')],
        ])->assertHasNoActionErrors();

        $adjunto = Attachment::query()
            ->where('attachable_type', Credit::class)
            ->where('attachable_id', $credito->id)
            ->firstOrFail();

        $this->assertSame('prima.jpg', $adjunto->original_name);
        $this->assertStringContainsString('credit/', $adjunto->path);
        $this->assertStringNotContainsString('temporary', $adjunto->path);
        $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));
    }

    public function test_una_factura_suelta_se_guarda_en_el_cliente(): void
    {
        $cliente = Customer::factory()->create();

        $this->subir($cliente, [
            'target' => 'customer',
            'files' => [UploadedFile::fake()->create('factura.pdf', 120, 'application/pdf')],
        ])->assertHasNoActionErrors();

        $adjunto = $cliente->attachments()->firstOrFail();

        $this->assertSame('factura.pdf', $adjunto->original_name);
        $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));
    }

    public function test_el_comprobante_se_guarda_en_la_cuota(): void
    {
        $cliente = Customer::factory()->create();
        $credito = Credit::factory()->create(['customer_id' => $cliente->id]);
        $cuota = Installment::factory()->create([
            'credit_id' => $credito->id,
            'number' => 1,
        ]);

        $this->subir($cliente, [
            'target' => 'installment',
            'credit_id' => $credito->id,
            'installment_id' => $cuota->id,
            'files' => [UploadedFile::fake()->image('voucher.png')],
        ])->assertHasNoActionErrors();

        $adjunto = Attachment::query()
            ->where('attachable_type', Installment::class)
            ->where('attachable_id', $cuota->id)
            ->firstOrFail();

        $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));
    }

    public function test_el_adjunto_aparece_en_la_ficha(): void
    {
        $cliente = Customer::factory()->create();
        $credito = Credit::factory()->create(['customer_id' => $cliente->id]);

        $this->subir($cliente, [
            'target' => 'credit',
            'credit_id' => $credito->id,
            'files' => [UploadedFile::fake()->image('prima.jpg')],
        ])->assertHasNoActionErrors();

        Livewire::test(\App\Livewire\CustomerAttachments::class, ['record' => $cliente->fresh()])
            ->assertOk()
            ->assertSee('prima.jpg');
    }

    public function test_pide_el_credito_cuando_el_destino_es_un_credito(): void
    {
        $cliente = Customer::factory()->create();

        $this->subir($cliente, [
            'target' => 'credit',
            'files' => [UploadedFile::fake()->image('prima.jpg')],
        ])->assertHasActionErrors(['credit_id']);

        $this->assertSame(0, Attachment::query()->count());
    }

    public function test_pide_al_menos_un_archivo(): void
    {
        $cliente = Customer::factory()->create();

        $this->subir($cliente, ['target' => 'customer'])
            ->assertHasActionErrors(['files']);

        $this->assertSame(0, Attachment::query()->count());
    }

    public function test_se_pueden_subir_varios_de_una_vez(): void
    {
        $cliente = Customer::factory()->create();

        $this->subir($cliente, [
            'target' => 'customer',
            'files' => [
                UploadedFile::fake()->image('frente.jpg'),
                UploadedFile::fake()->image('atras.jpg'),
            ],
        ])->assertHasNoActionErrors();

        $this->assertSame(2, $cliente->attachments()->count());
    }
}
