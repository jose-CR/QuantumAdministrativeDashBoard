<?php

namespace Tests\Feature\Attachments;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El adjunto que se carga al crear una entrada de caja tiene que quedar
 * registrado y en el disco. Antes el campo nunca guardaba el archivo.
 */
class InflowAttachmentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_una_entrada_guarda_su_adjunto(): void
    {
        $this->actingAs($this->admin());
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $cliente = Customer::factory()->create();

        Livewire::test(CreateInflow::class)
            ->fillForm([
                'invoice_number' => 'INV-ADJUNTO-1',
                'customer_id' => $cliente->id,
                'date' => now()->toDateString(),
                'salesperson' => 'José',
                'amount' => 250,
                'amount_paid' => 250,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
            ])
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
}
