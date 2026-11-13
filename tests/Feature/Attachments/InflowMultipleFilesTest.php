<?php

namespace Tests\Feature\Attachments;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Models\Customer;
use App\Models\Inflow;
use App\Services\AttachmentSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Una entrada puede llevar varios archivos y ninguno puede perderse: ni cuando dos
 * tienen el mismo nombre, ni al editar para agregar uno más.
 */
class InflowMultipleFilesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        Storage::fake('attachments');
    }

    private function datos(Customer $cliente): array
    {
        return [
            'invoice_number' => 'INV-MULTI-' . uniqid(),
            'customer_id' => $cliente->id,
            'date' => now()->toDateString(),
            'salesperson' => 'Prueba Multi',
            'amount' => 300,
            'amount_paid' => 300,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ];
    }

    public function test_dos_archivos_de_la_entrada_quedan_los_dos(): void
    {
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente))
            ->set('data.attachment_uploads', [
                UploadedFile::fake()->image('factura.jpg'),
                UploadedFile::fake()->create('comprobante.pdf', 40, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('salesperson', 'Prueba Multi')->latest('id')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['factura.jpg', 'comprobante.pdf'],
            $entrada->attachments()->pluck('original_name')->all(),
        );

        // Un archivo por adjunto, sin pisarse.
        foreach ($entrada->attachments()->get() as $adjunto) {
            $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));
        }

        $this->assertCount(2, $entrada->attachments()->distinct()->pluck('path'));
    }

    public function test_dos_archivos_con_el_mismo_nombre_quedan_los_dos(): void
    {
        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente))
            ->set('data.attachment_uploads', [
                UploadedFile::fake()->image('comprobante.jpg'),
                UploadedFile::fake()->image('comprobante.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('salesperson', 'Prueba Multi')->latest('id')->firstOrFail();

        $this->assertSame(2, $entrada->attachments()->count());
        $this->assertCount(2, $entrada->attachments()->distinct()->pluck('path'));
        $this->assertSame(['comprobante.jpg', 'comprobante.jpg'], $entrada->attachments()->pluck('original_name')->all());
    }

    public function test_al_editar_agregar_un_archivo_no_borra_el_anterior(): void
    {
        $cliente = Customer::factory()->create();

        $entrada = Inflow::factory()->create([
            'customer_id' => $cliente->id,
            'salesperson' => 'Prueba Multi',
        ]);

        $path = "inflow/{$entrada->id}-record/factura.jpg";
        Storage::disk('attachments')->put($path, 'x');

        $existente = $entrada->attachments()->create([
            'disk' => 'attachments',
            'path' => $path,
            'original_name' => 'factura.jpg',
        ]);

        // Lo que hace el formulario al guardar la edición: los paths que ya tenía
        // más el archivo nuevo.
        Storage::disk('attachments')->put('livewire-tmp/comprobante.pdf', 'x');

        app(AttachmentSyncService::class)->sync(
            record: $entrada,
            paths: [$existente->path, 'livewire-tmp/comprobante.pdf'],
            fileNames: ['livewire-tmp/comprobante.pdf' => 'comprobante.pdf'],
            collection: 'inflows',
        );

        $this->assertEqualsCanonicalizing(
            ['factura.jpg', 'comprobante.pdf'],
            $entrada->fresh()->attachments()->pluck('original_name')->all(),
        );
        $this->assertCount(2, $entrada->fresh()->attachments()->distinct()->pluck('path'));
    }
}
