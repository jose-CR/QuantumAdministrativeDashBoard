<?php

namespace Tests\Feature\Customers;

use App\Filament\Admin\Resources\Client\Customers\Pages\CreateCustomer;
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
