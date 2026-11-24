<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\Actions\CustomerStatementAction;
use App\Filament\Admin\Resources\Client\Customers\Pages\ViewCustomer;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Services\Reports\CustomerStatementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El Excel de estados de cuenta: una hoja por cliente, con sus créditos, cuotas y
 * pagos. Se prueba el contenido del libro, no solo que el botón exista.
 */
class CustomerStatementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function clienteConCredito(
        string $nombre,
        float $monto = 1200,
        int $cuotas = 2,
        string $primeraCuota = '2026-01-10',
        bool $pagada = false,
    ): array {
        $cliente = Customer::factory()->create(['full_name' => $nombre]);

        $credito = Credit::factory()->create([
            'customer_id' => $cliente->id,
            'status' => 'active',
            'installments' => $cuotas,
            'installment_amount' => $monto / $cuotas,
            'total_amount' => $monto,
            'pending_balance' => $monto,
        ]);

        for ($numero = 1; $numero <= $cuotas; $numero++) {
            $credito->installments()->create([
                'number' => $numero,
                'amount' => $monto / $cuotas,
                'remaining_balance' => $pagada ? 0 : $monto / $cuotas,
                'due_date' => date('Y-m-d', strtotime($primeraCuota . ' +' . ($numero - 1) . ' month')),
                'status' => $pagada ? 'paid' : 'pending',
                'paid_at' => $pagada ? now() : null,
            ]);
        }

        return [$cliente, $credito];
    }

    private function hojas(string $ruta): array
    {
        $script = 'import sys, json; from openpyxl import load_workbook; '
            . 'w = load_workbook(sys.argv[1]); print(json.dumps(w.sheetnames))';

        $salida = shell_exec(escapeshellarg(\App\Support\PythonBinary::path()) . ' -c ' . escapeshellarg($script) . ' ' . escapeshellarg($ruta));

        return json_decode(trim((string) $salida), true) ?: [];
    }

    public function test_arma_una_cuenta_por_cliente_con_todos_sus_creditos(): void
    {
        [$cliente] = $this->clienteConCredito('Cliente Dos Créditos');

        // Segundo crédito de la misma persona: va en la MISMA hoja.
        $segundo = Credit::factory()->create([
            'customer_id' => $cliente->id,
            'status' => 'active',
            'installments' => 1,
            'installment_amount' => 500,
            'total_amount' => 500,
            'pending_balance' => 500,
        ]);
        $segundo->installments()->create([
            'number' => 1, 'amount' => 500, 'remaining_balance' => 500,
            'due_date' => '2026-05-10', 'status' => 'pending',
        ]);

        $datos = app(CustomerStatementService::class)
            ->payload(app(CustomerStatementService::class)->customerCredits($cliente));

        $this->assertCount(1, $datos['clientes']);
        $this->assertCount(2, $datos['clientes'][0]['creditos']);
        $this->assertSame(1700.0, (float) $datos['clientes'][0]['resumen']['total']);
        $this->assertSame(2, (int) $datos['clientes'][0]['resumen']['creditos']);
    }

    public function test_cuenta_las_cuotas_vencidas_y_su_saldo(): void
    {
        [$cliente] = $this->clienteConCredito('Cliente Vencido', 1000, 4, '2026-01-10');

        $datos = app(CustomerStatementService::class)
            ->payload(app(CustomerStatementService::class)->customerCredits($cliente));

        $resumen = $datos['clientes'][0]['resumen'];

        // Las cuatro cuotas vencieron antes de hoy (noviembre 2026).
        $this->assertSame(4, (int) $resumen['vencidas']);
        $this->assertSame(1000.0, (float) $resumen['saldo_vencido']);
        $this->assertSame(0, (int) $resumen['cuotas_pagadas']);
    }

    public function test_genera_el_excel_con_hoja_por_cliente_vendedores_y_resumen(): void
    {
        [$uno] = $this->clienteConCredito('Ana Uno');
        [$dos] = $this->clienteConCredito('Beto Dos');

        $datos = app(CustomerStatementService::class)
            ->payload(app(CustomerStatementService::class)->customerCredits($uno)
                ->concat(app(CustomerStatementService::class)->customerCredits($dos)));

        $ruta = app(CustomerStatementService::class)->build(
            app(CustomerStatementService::class)->customerCredits($uno)
                ->concat(app(CustomerStatementService::class)->customerCredits($dos))
        );

        $this->assertFileExists($ruta);

        $hojas = $this->hojas($ruta);

        $this->assertContains('Resumen', $hojas);
        $this->assertContains('Vendedores', $hojas);
        $this->assertCount(4, $hojas); // Resumen + Vendedores + 2 clientes

        @unlink($ruta);
    }

    public function test_el_vendedor_sale_de_la_ultima_entrada_de_caja(): void
    {
        [$cliente] = $this->clienteConCredito('Cliente Con Vendedor');

        Inflow::factory()->create([
            'customer_id' => $cliente->id,
            'salesperson' => 'Vendedor Real',
            'invoice_number' => 'INV-EST-' . uniqid(),
            'date' => '2026-10-01',
            'payment_status' => 'pending',
        ]);

        $datos = app(CustomerStatementService::class)
            ->payload(app(CustomerStatementService::class)->customerCredits($cliente));

        $this->assertSame('Vendedor Real', $datos['clientes'][0]['vendedor']);
        $this->assertSame('Vendedor Real', $datos['vendedores'][0]['vendedor']);
    }

    public function test_sin_creditos_no_se_puede_generar(): void
    {
        $this->expectException(\RuntimeException::class);

        app(CustomerStatementService::class)->build(collect());
    }

    public function test_la_accion_del_tablero_se_arma(): void
    {
        $this->assertSame('estadoDeCuenta', CustomerStatementAction::overdue()->getName());
        $this->assertSame(__('ui.statement.overdue'), CustomerStatementAction::overdue()->getLabel());
    }
}
