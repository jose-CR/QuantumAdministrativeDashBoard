<?php

namespace Tests\Feature\Customers;

use App\Livewire\CustomerAttachments;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Installment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El visor de adjuntos del cliente en tres grupos (créditos, cuotas y sueltos):
 * contar, borrar y no tocar lo de otro cliente.
 */
class CustomerAttachmentsTest extends TestCase
{
    use DatabaseTransactions;

    private function clienteConCredito(): array
    {
        $cliente = Customer::factory()->create();

        $credito = $cliente->credits()->create([
            'initial_amount' => 500,
            'financed_amount' => 500,
            'installments' => 1,
            'installment_amount' => 500,
            'total_amount' => 500,
            'pending_balance' => 500,
            'start_date' => today()->toDateString(),
            'payment_day' => 1,
            'status' => 'active',
        ]);

        $cuota = $credito->installments()->create([
            'number' => 1,
            'amount' => 500,
            'remaining_balance' => 500,
            'due_date' => today()->addMonth()->toDateString(),
            'status' => 'pending',
        ]);

        return [$cliente, $credito, $cuota];
    }

    // La subida se prueba en tests/Feature/Attachments/AttachFileTest.php: ahora
    // la hace la acción «Adjuntar archivo» con el FileUpload de Filament.

    public function test_los_tabs_cuentan_lo_que_corresponde(): void
    {
        Storage::fake('attachments');

        [$cliente, $credito, $cuota] = $this->clienteConCredito();

        $credito->attachments()->create(['path' => 'a/uno.pdf', 'original_name' => 'uno.pdf']);
        $cuota->attachments()->create(['path' => 'a/dos.pdf', 'original_name' => 'dos.pdf']);
        $cliente->attachments()->create(['path' => 'a/tres.pdf', 'original_name' => 'tres.pdf']);

        $componente = Livewire::test(CustomerAttachments::class, ['record' => $cliente]);

        $this->assertSame(
            ['credits' => 1, 'installments' => 1, 'loose' => 1],
            $componente->instance()->counts,
        );
    }

    public function test_no_puede_borrar_el_adjunto_de_otro_cliente(): void
    {
        Storage::fake('attachments');

        [$cliente] = $this->clienteConCredito();
        [, $creditoAjeno] = $this->clienteConCredito();

        $ajeno = $creditoAjeno->attachments()->create([
            'path' => 'a/ajeno.pdf',
            'original_name' => 'ajeno.pdf',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(CustomerAttachments::class, ['record' => $cliente])
            ->call('delete', $ajeno->id);
    }

    public function test_el_borrado_pide_confirmacion_en_un_modal_y_no_en_el_navegador(): void
    {
        Storage::fake('attachments');

        [$cliente, $credito] = $this->clienteConCredito();

        $credito->attachments()->create(['path' => 'a/borrar.pdf', 'original_name' => 'borrar.pdf']);

        $componente = Livewire::test(CustomerAttachments::class, ['record' => $cliente])
            ->call('setTab', 'credits')
            ->assertOk();

        // El modal del panel, no el diálogo del navegador.
        $componente->assertSee('¿Eliminar este adjunto?');
        $componente->assertDontSee('wire:confirm', false);
    }

    public function test_borra_un_adjunto_del_credito(): void
    {
        Storage::fake('attachments');

        [$cliente, $credito] = $this->clienteConCredito();

        $adjunto = $credito->attachments()->create([
            'path' => 'a/borrar.pdf',
            'original_name' => 'borrar.pdf',
        ]);

        Livewire::test(CustomerAttachments::class, ['record' => $cliente])
            ->call('setTab', 'credits')
            ->call('delete', $adjunto->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('attachments', ['id' => $adjunto->id]);
    }

    public function test_la_ficha_del_cliente_monta_los_adjuntos(): void
    {
        $cliente = Customer::factory()->create();

        $this->actingAs($this->admin());
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        Livewire::test(\App\Filament\Admin\Resources\Client\Customers\Pages\ViewCustomer::class, [
            'record' => $cliente->getKey(),
        ])
            ->assertOk()
            ->assertSee('Adjuntos');
    }
}
