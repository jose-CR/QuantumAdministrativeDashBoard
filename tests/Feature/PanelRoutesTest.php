<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Outflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Humo del panel: toda pantalla que existe tiene que abrir.
 *
 * Es la capa que caza el error de «Livewire encountered a missing tag»: una vista de
 * componente con dos raíces, una clase sin importar o un componente que no existe
 * revientan acá y no en la cara del taller.
 */
class PanelRoutesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_todas_las_pantallas_del_panel_abren(): void
    {
        $this->actingAs($this->admin());

        $cliente = Customer::factory()->create();
        $credito = Credit::factory()->create(['customer_id' => $cliente->id]);
        $entrada = Inflow::factory()->create(['amount' => 100, 'amount_paid' => 50]);
        $salida = Outflow::factory()->create();

        $rutas = [
            '/admin',
            '/admin/apps',
            '/admin/data',

            // listados
            '/admin/client/customers',
            '/admin/administration/inflows',
            '/admin/administration/outflows',
            '/admin/administration/banks',
            '/admin/credits/loans',
            '/admin/credits/installments',
            '/admin/credits/history',
            '/admin/inventory/articles',
            '/admin/inventory/units',
            '/admin/inventory/categories',
            '/admin/inventory/transportation',
            '/admin/tags',
            '/admin/users',
            '/admin/shield/roles',

            // altas y ediciones
            '/admin/client/customers/create',
            '/admin/client/customers/' . $cliente->id,
            '/admin/client/customers/' . $cliente->id . '/edit',
            '/admin/credits/loans/create?customer=' . $cliente->id,
            '/admin/credits/loans/' . $credito->id . '/edit',
            '/admin/credits/history/create',
            '/admin/administration/inflows/create',
            '/admin/administration/inflows/' . $entrada->id . '/edit',
            '/admin/administration/outflows/create',
            '/admin/administration/outflows/' . $salida->id . '/edit',
            '/admin/administration/banks/create',
            '/admin/inventory/articles/create',
            '/admin/inventory/units/create',
            '/admin/inventory/categories/create',
            '/admin/inventory/transportation/create',
            '/admin/users/create',
        ];

        $fallidas = [];

        foreach ($rutas as $ruta) {
            $codigo = $this->get($ruta)->getStatusCode();

            if ($codigo >= 400) {
                $fallidas[] = "{$ruta} → {$codigo}";
            }
        }

        $this->assertSame([], $fallidas, "Pantallas que no abren:\n" . implode("\n", $fallidas));
    }

    public function test_las_vistas_de_livewire_tienen_una_sola_raiz(): void
    {
        // Livewire exige un único elemento raíz en la vista del componente; con dos,
        // el error es «Livewire encountered a missing tag».
        foreach (glob(resource_path('views/livewire/*.blade.php')) as $archivo) {
            $html = file_get_contents($archivo);

            $this->assertSame(
                1,
                preg_match_all('/^<div\b/m', $html),
                basename($archivo) . ' debería abrir con un único <div> raíz',
            );
        }
    }
}
