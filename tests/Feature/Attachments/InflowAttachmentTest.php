<?php

namespace Tests\Feature\Attachments;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Models\Attachment;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Adjuntos de una entrada de caja: se puede reusar lo que el cliente ya tiene y lo
 * que se sube acá queda también en el cliente.
 */
class InflowAttachmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function datos(Customer $cliente, string $factura = 'INV-ADJUNTO-1'): array
    {
        return [
            'invoice_number' => $factura,
            'customer_id' => $cliente->id,
            'date' => now()->toDateString(),
            'salesperson' => 'José',
            'amount' => 250,
            'amount_paid' => 250,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ];
    }

    private function adjuntoDelCliente(Customer $cliente, string $nombre = 'factura-cliente.pdf'): Attachment
    {
        Storage::disk('attachments')->put("customer/{$cliente->id}/{$nombre}", 'contenido');

        return $cliente->attachments()->create([
            'disk' => 'attachments',
            'path' => "customer/{$cliente->id}/{$nombre}",
            'original_name' => $nombre,
        ]);
    }

    public function test_una_entrada_guarda_su_adjunto(): void
    {
        Storage::fake('attachments');

        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente))
            ->set('data.attachment_uploads', [UploadedFile::fake()->image('factura.jpg')])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('invoice_number', 'INV-ADJUNTO-1')->firstOrFail();

        $adjunto = Attachment::query()
            ->where('attachable_type', Inflow::class)
            ->where('attachable_id', $entrada->id)
            ->firstOrFail();

        $this->assertSame('factura.jpg', $adjunto->original_name);
        $this->assertStringContainsString('inflow/', $adjunto->path);
        $this->assertStringNotContainsString('livewire-tmp', $adjunto->path);
        $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));
    }

    public function test_lo_que_se_sube_en_la_entrada_queda_tambien_en_el_cliente(): void
    {
        Storage::fake('attachments');

        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente, 'INV-ESPEJO-1'))
            ->set('data.attachment_uploads', [UploadedFile::fake()->image('factura.jpg')])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('invoice_number', 'INV-ESPEJO-1')->firstOrFail();

        $deLaEntrada = $entrada->attachments()->firstOrFail();
        $delCliente = $cliente->attachments()->firstOrFail();

        // Mismo archivo, dos filas: vive en la entrada y en el cliente.
        $this->assertSame($deLaEntrada->path, $delCliente->path);
        $this->assertCount(1, collect(Storage::disk('attachments')->allFiles())
            ->filter(fn (string $ruta): bool => str_ends_with($ruta, 'factura.jpg')));
    }

    public function test_se_puede_reusar_un_adjunto_que_ya_tiene_el_cliente(): void
    {
        Storage::fake('attachments');

        $cliente = Customer::factory()->create();
        $existente = $this->adjuntoDelCliente($cliente);

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente, 'INV-REUSO-1'))
            ->set('data.attachment_picks', [$existente->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('invoice_number', 'INV-REUSO-1')->firstOrFail();

        $adjunto = $entrada->attachments()->firstOrFail();

        // No se copia el archivo: la fila apunta al mismo path del cliente.
        $this->assertSame($existente->path, $adjunto->path);
        $this->assertSame('factura-cliente.pdf', $adjunto->original_name);
        $this->assertSame(1, $cliente->attachments()->count());
        $this->assertCount(1, collect(Storage::disk('attachments')->allFiles())
            ->filter(fn (string $ruta): bool => str_ends_with($ruta, 'factura-cliente.pdf')));
    }

    public function test_se_puede_reusar_un_adjunto_de_una_cuota_del_cliente(): void
    {
        Storage::fake('attachments');

        $cliente = Customer::factory()->create();
        $credito = Credit::factory()->create(['customer_id' => $cliente->id]);
        $cuota = $credito->installments()->create([
            'number' => 1,
            'amount' => 100,
            'remaining_balance' => 100,
            'due_date' => today()->toDateString(),
            'status' => 'pending',
        ]);

        Storage::disk('attachments')->put('installment/1/comprobante.pdf', 'contenido');

        $comprobante = $cuota->attachments()->create([
            'disk' => 'attachments',
            'path' => 'installment/1/comprobante.pdf',
            'original_name' => 'comprobante.pdf',
        ]);

        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente, 'INV-REUSO-CUOTA'))
            ->set('data.attachment_picks', [$comprobante->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $entrada = Inflow::query()->where('invoice_number', 'INV-REUSO-CUOTA')->firstOrFail();

        $this->assertSame('installment/1/comprobante.pdf', $entrada->attachments()->firstOrFail()->path);
        // El comprobante sigue en su cuota y ahora también en la entrada.
        $this->assertSame(1, $cuota->attachments()->count());
    }

    public function test_no_se_puede_reusar_el_adjunto_de_otro_cliente(): void
    {
        Storage::fake('attachments');

        $cliente = Customer::factory()->create();
        $otro = Customer::factory()->create();

        $ajeno = $this->adjuntoDelCliente($otro, 'ajeno.pdf');

        // Filament valida el select contra las opciones de ESE cliente, así que el
        // archivo de otro cliente se rechaza y la entrada no se guarda.
        Livewire::test(CreateInflow::class)
            ->fillForm($this->datos($cliente, 'INV-AJENO-1'))
            ->set('data.attachment_picks', [$ajeno->id])
            ->call('create')
            ->assertHasFormErrors(['attachment_picks.0']);

        $this->assertNull(Inflow::query()->where('invoice_number', 'INV-AJENO-1')->first());
    }
}
