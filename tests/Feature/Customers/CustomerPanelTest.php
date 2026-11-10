<?php

namespace Tests\Feature\Customers;

use App\Filament\Admin\Resources\Client\Customers\Pages\CreateCustomer;
use App\Filament\Admin\Resources\Client\Customers\RelationManagers\ReferencesRelationManager;
use App\Filament\Admin\Resources\Client\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Client\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El formulario de clientes: dos personas con el mismo nombre se pueden cargar,
 * pero el mismo documento no (y el error tiene que ser del formulario, no un 500).
 */
class CustomerPanelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_el_listado_y_la_ficha_del_cliente_responden(): void
    {
        Livewire::test(ListCustomers::class)->assertOk();

        $cliente = Customer::query()->firstOrFail();

        Livewire::test(ViewCustomer::class, ['record' => $cliente->getKey()])->assertOk();
    }

    public function test_la_ficha_muestra_la_cuenta_por_cobrar(): void
    {
        $cliente = Customer::factory()->create();

        $credito = $cliente->credits()->create([
            'initial_amount' => 500,
            'financed_amount' => 500,
            'installments' => 2,
            'installment_amount' => 250,
            'total_amount' => 500,
            'pending_balance' => 500,
            'start_date' => today()->subMonth()->toDateString(),
            'payment_day' => 1,
            'status' => 'active',
        ]);

        $credito->installments()->create([
            'number' => 1,
            'amount' => 250,
            'remaining_balance' => 250,
            'due_date' => today()->subDays(10)->toDateString(),
            'status' => 'late',
        ]);

        $credito->installments()->create([
            'number' => 2,
            'amount' => 250,
            'remaining_balance' => 250,
            'due_date' => today()->addDays(20)->toDateString(),
            'status' => 'pending',
        ]);

        Livewire::test(ViewCustomer::class, ['record' => $cliente->getKey()])
            ->assertOk()
            ->assertSee('Cuentas por cobrar')
            ->assertSee('Total por cobrar')
            ->assertSee('Vencido')
            ->assertSee('Próximo vencimiento');
    }

    public function test_las_referencias_del_cliente_se_listan_y_se_crean(): void
    {
        $cliente = Customer::factory()->create();

        $referencia = $cliente->references()->create([
            'full_name' => 'Rosa Martínez',
            'reference_type' => 'family',
            'relationship' => 'Madre',
            'phone' => '7777-1234',
        ]);

        // La tabla de una relación se prueba montando el relation manager: en la
        // ficha completa la tabla se carga después del primer render.
        Livewire::test(ReferencesRelationManager::class, [
            'ownerRecord' => $cliente,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$referencia])
            ->assertTableColumnStateSet('reference_type', 'family', $referencia);

        // Y la ficha las muestra bajo su sección.
        Livewire::test(ViewCustomer::class, ['record' => $cliente->getKey()])
            ->assertOk()
            ->assertSee('Referencias');
    }

    public function test_se_pueden_cargar_dos_clientes_con_el_mismo_nombre(): void
    {
        $existente = Customer::factory()->create([
            'document_type' => 'OTRO',
            'document_number' => 'MISMO-NOMBRE-1',
            'full_name' => 'María Elena Rodríguez',
        ]);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'document_type' => 'OTRO',
                'document_number' => 'MISMO-NOMBRE-2',
                'full_name' => $existente->full_name,
                'department' => $existente->department,
                'municipality' => $existente->municipality,
                'district' => $existente->district,
                'address' => 'Calle nueva 123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(
            2,
            Customer::query()->where('full_name', 'María Elena Rodríguez')->count()
        );
    }

    public function test_el_mismo_documento_da_error_de_formulario_y_no_un_500(): void
    {
        $existente = Customer::factory()->create([
            'document_type' => 'OTRO',
            'document_number' => 'DOC-REPETIDO-1',
        ]);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'document_type' => 'OTRO',
                'document_number' => 'DOC-REPETIDO-1',
                'full_name' => 'Otro cliente distinto',
                'department' => $existente->department,
                'municipality' => $existente->municipality,
                'district' => $existente->district,
                'address' => 'Calle nueva 456',
            ])
            ->call('create')
            ->assertHasFormErrors(['document_number']);

        $this->assertFalse(
            Customer::query()->where('full_name', 'Otro cliente distinto')->exists()
        );
    }
}
