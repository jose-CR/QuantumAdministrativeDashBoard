<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\ListOutflows;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Outflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ingresos y egresos ordenados: fecha más reciente primero, buscador por factura /
 * cliente / empresa y filtro por mes.
 */
class FlowOrderingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    private function entrada(string $factura, string $fecha, string $cliente, ?string $vendedor = null): Inflow
    {
        return Inflow::factory()->create([
            'customer_id' => Customer::factory()->create(['full_name' => $cliente])->id,
            'invoice_number' => $factura,
            'date' => $fecha,
            'salesperson' => $vendedor ?? 'Sin vendedor',
            'payment_status' => 'pending',
        ]);
    }

    private function salida(string $codigo, string $fecha, string $empresa): Outflow
    {
        return Outflow::factory()->create([
            'invoice_code' => $codigo,
            'invoice_date' => $fecha,
            'company' => $empresa,
        ]);
    }

    public function test_los_ingresos_salen_con_la_fecha_mas_nueva_primero(): void
    {
        $vieja = $this->entrada('F-VIEJA', '2026-10-01', 'Cliente Vieja');
        $media = $this->entrada('F-MEDIA', '2026-10-15', 'Cliente Media');
        $nueva = $this->entrada('F-NUEVA', '2026-11-02', 'Cliente Nueva');

        Livewire::test(ListInflows::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$nueva, $media, $vieja], inOrder: true);
    }

    public function test_el_buscador_encuentra_por_factura_cliente_y_vendedor(): void
    {
        $buscada = $this->entrada('F-AB-123', '2026-10-15', 'María Buscada', 'Vendedor Uno');
        $otra = $this->entrada('F-OTRA', '2026-10-16', 'Otro Cliente', 'Vendedor Dos');

        Livewire::test(ListInflows::class)
            ->searchTable('AB-123')
            ->assertCanSeeTableRecords([$buscada])
            ->assertCanNotSeeTableRecords([$otra]);

        Livewire::test(ListInflows::class)
            ->searchTable('María Buscada')
            ->assertCanSeeTableRecords([$buscada]);

        Livewire::test(ListInflows::class)
            ->searchTable('Vendedor Dos')
            ->assertCanSeeTableRecords([$otra]);
    }

    public function test_el_filtro_de_mes_deja_solo_ese_mes(): void
    {
        $octubre = $this->entrada('F-OCT', '2026-10-15', 'Cliente Octubre');
        $noviembre = $this->entrada('F-NOV', '2026-11-03', 'Cliente Noviembre');

        Livewire::test(ListInflows::class)
            ->filterTable('mes', ['mes' => '2026-10'])
            ->assertCanSeeTableRecords([$octubre])
            ->assertCanNotSeeTableRecords([$noviembre]);
    }

    public function test_los_egresos_tambien_van_por_fecha_y_tienen_buscador(): void
    {
        $vieja = $this->salida('C-001', '2026-09-10', 'Proveedor Viejo');
        $nueva = $this->salida('C-002', '2026-11-05', 'Proveedor Nuevo');

        Livewire::test(ListOutflows::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$nueva, $vieja], inOrder: true)
            ->searchTable('C-002')
            ->assertCanSeeTableRecords([$nueva])
            ->assertCanNotSeeTableRecords([$vieja]);
    }

    public function test_los_egresos_filtran_por_mes(): void
    {
        $octubre = $this->salida('C-OCT', '2026-10-20', 'Proveedor Octubre');
        $noviembre = $this->salida('C-NOV', '2026-11-01', 'Proveedor Noviembre');

        Livewire::test(ListOutflows::class)
            ->filterTable('mes', ['mes' => '2026-10'])
            ->assertCanSeeTableRecords([$octubre])
            ->assertCanNotSeeTableRecords([$noviembre]);
    }
}
