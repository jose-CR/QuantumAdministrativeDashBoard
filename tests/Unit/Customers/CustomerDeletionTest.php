<?php

namespace Tests\Unit\Customers;

use App\Models\Customer;
use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Borrar un cliente con historial se llevaba sus créditos, cuotas, pagos y
 * alertas por la cascada de la base. Ahora se frena.
 */
class CustomerDeletionTest extends TestCase
{
    use DatabaseTransactions;

    private function clienteConCredito(): Customer
    {
        $cliente = Customer::factory()->create();

        $cliente->credits()->create([
            'initial_amount' => 100,
            'financed_amount' => 100,
            'installments' => 1,
            'installment_amount' => 100,
            'total_amount' => 100,
            'pending_balance' => 100,
            'start_date' => today()->toDateString(),
            'payment_day' => 1,
            'status' => 'active',
        ]);

        return $cliente;
    }

    public function test_un_cliente_con_creditos_no_se_borra(): void
    {
        $cliente = $this->clienteConCredito();

        $this->assertFalse($cliente->canBeDeleted());
        $this->assertFalse($cliente->delete());

        $this->assertDatabaseHas('customers', ['id' => $cliente->id]);
        $this->assertSame(1, $cliente->credits()->count());
    }

    public function test_el_resumen_dice_que_lo_bloquea(): void
    {
        $cliente = $this->clienteConCredito();

        $resumen = $cliente->deletionSummary();

        $this->assertSame(1, $resumen['credits']);
        $this->assertSame(1, $resumen['credits_active']);
        $this->assertSame(0, $resumen['invoices']);

        $this->assertSame(
            [['id' => $cliente->credits()->value('id'), 'status' => 'active']],
            $cliente->creditsBlockingDeletion()->all()
        );
    }

    public function test_un_cliente_con_facturas_no_se_borra(): void
    {
        // inflows.customer_id no tiene cascade: borrar reventaba con error de base.
        $cliente = Customer::factory()->create();

        Inflow::factory()->create(['customer_id' => $cliente->id]);

        $this->assertFalse($cliente->canBeDeleted());
        $this->assertFalse($cliente->delete());

        $this->assertDatabaseHas('customers', ['id' => $cliente->id]);
    }

    public function test_un_cliente_sin_historial_se_borra_con_sus_adjuntos(): void
    {
        // El fake va después de crear el cliente: la factory lee el catálogo de
        // actividades económicas del disco, y falsearlo antes lo deja sin datos.
        $cliente = Customer::factory()->create();

        Storage::fake('local');

        Storage::disk('local')->put('clientes/factura.pdf', 'contenido');

        $adjunto = $cliente->attachments()->create([
            'collection' => 'documentos',
            'disk' => 'local',
            'path' => 'clientes/factura.pdf',
            'original_name' => 'factura.pdf',
            'mime_type' => 'application/pdf',
            'size' => 9,
        ]);

        $this->assertTrue($cliente->canBeDeleted());
        $this->assertTrue($cliente->delete());

        $this->assertDatabaseMissing('customers', ['id' => $cliente->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $adjunto->id]);
        Storage::disk('local')->assertMissing('clientes/factura.pdf');
    }
}
