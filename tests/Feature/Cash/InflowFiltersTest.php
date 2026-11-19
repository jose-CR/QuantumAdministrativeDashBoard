<?php

namespace Tests\Feature\Cash;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inflow;
use App\Models\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Los filtros de Ingresos: etiqueta del cliente y empleado (vendedor anotado).
 */
class InflowFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    private function entrada(string $cliente, array $etiquetas = [], ?string $vendedor = null): Inflow
    {
        $customer = Customer::factory()->create(['full_name' => $cliente]);

        foreach ($etiquetas as $etiqueta) {
            $customer->attachTag(Tag::findOrCreate($etiqueta, 'customer'));
        }

        return Inflow::factory()->create([
            'customer_id' => $customer->id,
            'salesperson' => $vendedor ?? 'Sin vendedor',
            'invoice_number' => 'INV-' . uniqid(),
        ]);
    }

    public function test_filtra_por_etiqueta_del_cliente(): void
    {
        $mayorista = $this->entrada('Cliente Mayorista', ['mayorista']);
        $taller = $this->entrada('Cliente Taller', ['taller']);

        Livewire::test(ListInflows::class)
            ->filterTable('tag', ['mayorista'])
            ->assertCanSeeTableRecords([$mayorista])
            ->assertCanNotSeeTableRecords([$taller]);
    }

    public function test_filtra_por_empleado(): void
    {
        Employee::query()->create([
            'name' => 'Vendedor Uno',
            'document_type' => 'dui',
            'document_number' => '90000001-1',
            'job_title' => 'vendedor',
            'status' => 'active',
        ]);

        $suyo = $this->entrada('Cliente De Uno', [], 'Vendedor Uno');
        $ajeno = $this->entrada('Cliente De Otro', [], 'Vendedor Dos');

        Livewire::test(ListInflows::class)
            ->filterTable('employee', 'Vendedor Uno')
            ->assertCanSeeTableRecords([$suyo])
            ->assertCanNotSeeTableRecords([$ajeno]);
    }
}
