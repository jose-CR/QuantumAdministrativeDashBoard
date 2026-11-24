<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\Actions\AccountStatementAction;
use App\Filament\Admin\Resources\Client\Customers\Pages\ViewCustomer;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\PaymentHistory;
use App\Services\Reports\AccountStatementService;
use App\Support\PythonBinary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Estado de cuenta por cliente: todas las facturas como cargos, todos los pagos como
 * abonos, las cuotas como plan de pago y la antigüedad del saldo.
 */
class AccountStatementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function factura(
        Customer $cliente,
        string $numero,
        float $monto,
        float $cobrado,
        string $fecha,
        ?string $descripcion = null,
    ): Inflow {
        return Inflow::factory()->create([
            'customer_id' => $cliente->id,
            'invoice_number' => $numero,
            'amount' => $monto,
            'amount_paid' => $cobrado,
            'date' => $fecha,
            'description' => $descripcion ?? 'Servicio ' . $numero,
            'salesperson' => 'Sin vendedor',
            'document_type' => 'cf',
            // El estado se deriva del cobrado; si llega "paid" con cobro 0 el modelo lo
            // completa al total.
            'payment_status' => 'pending',
        ]);
    }

    private function estado(Customer $cliente): array
    {
        return app(AccountStatementService::class)->forCustomer($cliente);
    }

    public function test_el_saldo_sale_de_las_facturas_menos_los_abonos(): void
    {
        $cliente = Customer::factory()->create();

        $this->factura($cliente, 'F-001', 1000, 1000, '2026-09-15');
        $this->factura($cliente, 'F-002', 2000, 500, '2026-10-10');
        $this->factura($cliente, 'F-003', 300, 0, '2026-11-20');

        $resumen = $this->estado($cliente)['resumen'];

        $this->assertSame(3300.0, (float) $resumen['facturado']);
        $this->assertSame(1500.0, (float) $resumen['abonado']);
        $this->assertSame(1800.0, (float) $resumen['saldo']);
        $this->assertSame(3, (int) $resumen['facturas']);
        $this->assertSame(1, (int) $resumen['pagadas']);
        $this->assertSame(2, (int) $resumen['con_saldo']);
    }

    public function test_el_estado_de_cada_factura_se_deriva_del_monto(): void
    {
        $cliente = Customer::factory()->create();

        $this->factura($cliente, 'F-010', 500, 500, '2026-10-01');
        $this->factura($cliente, 'F-011', 500, 200, '2026-10-02');
        $this->factura($cliente, 'F-012', 500, 0, '2026-10-03');

        $estados = collect($this->estado($cliente)['facturas'])->pluck('estado', 'numero');

        $this->assertSame('pagado', $estados['F-010']);
        $this->assertSame('parcial', $estados['F-011']);
        $this->assertSame('pendiente', $estados['F-012']);
    }

    public function test_las_que_deben_van_primero_y_de_la_mas_vieja_a_la_mas_nueva(): void
    {
        $cliente = Customer::factory()->create();

        $this->factura($cliente, 'PAGADA', 100, 100, '2026-11-01');
        $this->factura($cliente, 'VIEJA', 100, 0, '2026-09-01');
        $this->factura($cliente, 'NUEVA', 100, 0, '2026-10-01');

        $orden = collect($this->estado($cliente)['facturas'])->pluck('numero')->all();

        $this->assertSame(['VIEJA', 'NUEVA', 'PAGADA'], $orden);
    }

    public function test_la_antiguedad_reparte_el_saldo_por_tramos(): void
    {
        $cliente = Customer::factory()->create();

        // Vencidas hace más de 90 días y hace 15 días.
        $this->factura($cliente, 'A', 1000, 0, now()->subDays(120)->toDateString());
        $this->factura($cliente, 'B', 200, 0, now()->subDays(15)->toDateString());
        // Sin vencer todavía: no entra en la antigüedad.
        $this->factura($cliente, 'C', 500, 0, now()->addDays(10)->toDateString());

        $aging = $this->estado($cliente)['aging'];

        $this->assertSame(200.0, (float) $aging['d1_30']);
        $this->assertSame(0.0, (float) $aging['d31_60']);
        $this->assertSame(1000.0, (float) $aging['d90']);
        $this->assertSame(1200.0, (float) $aging['total']);
    }

    public function test_el_plan_de_cuotas_no_suma_al_saldo(): void
    {
        $cliente = Customer::factory()->create();
        $this->factura($cliente, 'F-100', 1200, 0, '2026-10-01');

        $credito = Credit::factory()->create([
            'customer_id' => $cliente->id,
            'status' => 'active',
            'installments' => 2,
            'installment_amount' => 600,
            'total_amount' => 1200,
            'pending_balance' => 1200,
        ]);

        foreach ([1, 2] as $numero) {
            $credito->installments()->create([
                'number' => $numero,
                'amount' => 600,
                'remaining_balance' => 600,
                'due_date' => "2026-11-0{$numero}",
                'status' => 'pending',
            ]);
        }

        $datos = $this->estado($cliente);

        // El saldo es el de la factura, no la suma de la factura más las cuotas.
        $this->assertSame(1200.0, (float) $datos['resumen']['saldo']);

        $plan = $datos['planes'][0];
        $this->assertCount(2, $plan['cuotas']);
        $this->assertSame(1200.0, (float) $plan['total']);
        // El crédito se reconoce con su factura por el monto.
        $this->assertSame('F-100', $plan['factura']);
    }

    public function test_con_credito_lo_vencido_son_solo_las_cuotas_ya_vencidas(): void
    {
        $cliente = Customer::factory()->create();
        $this->factura($cliente, 'F-200', 1200, 0, '2026-10-01');

        $credito = Credit::factory()->create([
            'customer_id' => $cliente->id,
            'status' => 'active',
            'installments' => 4,
            'installment_amount' => 300,
            'total_amount' => 1200,
            'pending_balance' => 1200,
        ]);

        // Dos cuotas vencidas y dos futuras: solo las primeras son deuda atrasada.
        $vencimientos = [now()->subDays(45), now()->subDays(10), now()->addDays(20), now()->addDays(50)];

        foreach ($vencimientos as $indice => $vence) {
            $credito->installments()->create([
                'number' => $indice + 1,
                'amount' => 300,
                'remaining_balance' => 300,
                'due_date' => $vence->toDateString(),
                'status' => $vence->isPast() ? 'late' : 'pending',
            ]);
        }

        $datos = $this->estado($cliente);

        $this->assertSame(600.0, (float) $datos['resumen']['vencido']);
        $this->assertSame(600.0, (float) $datos['resumen']['por_vencer']);
        // La antigüedad usa la fecha de cada cuota: 300 a 31-60 y 300 a 1-30.
        $this->assertSame(300.0, (float) $datos['aging']['d31_60']);
        $this->assertSame(300.0, (float) $datos['aging']['d1_30']);
        $this->assertSame(600.0, (float) $datos['aging']['total']);
    }

    public function test_los_recibos_traen_comprobante_banco_y_quien_registro(): void
    {
        $cliente = Customer::factory()->create();

        $credito = Credit::factory()->create(['customer_id' => $cliente->id, 'status' => 'active']);

        PaymentHistory::query()->create([
            'credit_id' => $credito->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-10-05',
            'receipt_number' => 'REC-777',
            'previous_balance' => 1000,
            'new_balance' => 500,
            'notes' => 'Abono en efectivo',
            'user_id' => $this->admin()->id,
        ]);

        $recibo = $this->estado($cliente)['recibos'][0];

        $this->assertSame('REC-777', $recibo['numero']);
        $this->assertSame('05/10/2026', $recibo['fecha']);
        $this->assertSame(500.0, (float) $recibo['monto']);
        $this->assertSame($this->admin()->name, $recibo['usuario']);
    }

    public function test_genera_el_excel_con_las_secciones(): void
    {
        $cliente = Customer::factory()->create(['full_name' => 'Menores S.A. de C.V.']);

        $this->factura($cliente, 'F-900', 1500, 500, '2026-10-01', 'Carrito eléctrico');

        $ruta = AccountStatementAction::build($cliente);

        $this->assertFileExists($ruta);

        $script = 'import sys, json; from openpyxl import load_workbook; '
            . 'w = load_workbook(sys.argv[1]); '
            . 'h = w.worksheets[0]; '
            . 'print(json.dumps({"hojas": w.sheetnames, "texto": " ".join(str(c.value) for f in h.iter_rows() for c in f if c.value)}))';

        $salida = shell_exec(
            escapeshellarg(PythonBinary::path()) . ' -c ' . escapeshellarg($script) . ' ' . escapeshellarg($ruta)
        );

        $datos = json_decode(trim((string) $salida), true);

        $this->assertSame(['Estado de cuenta'], $datos['hojas']);
        foreach (['ESTADO DE CUENTA', 'Menores S.A. de C.V.', 'FACTURAS', 'F-900', 'ANTIGÜEDAD', 'RECIBOS'] as $texto) {
            $this->assertStringContainsString($texto, $datos['texto'], "Falta «{$texto}» en el documento.");
        }

        @unlink($ruta);
    }

    public function test_el_boton_esta_en_la_ficha_solo_si_hay_facturas(): void
    {
        $conFacturas = Customer::factory()->create();
        $this->factura($conFacturas, 'F-950', 100, 0, '2026-10-01');

        $sinFacturas = Customer::factory()->create();

        Livewire::test(ViewCustomer::class, ['record' => $conFacturas->getKey()])
            ->assertOk()
            ->assertSee(__('ui.statement.customer_excel'));

        Livewire::test(ViewCustomer::class, ['record' => $sinFacturas->getKey()])
            ->assertOk()
            ->assertDontSee(__('ui.statement.customer_excel'));
    }

    public function test_sin_facturas_no_genera_nada(): void
    {
        $this->expectException(\RuntimeException::class);

        AccountStatementAction::build(Customer::factory()->create());
    }
}
