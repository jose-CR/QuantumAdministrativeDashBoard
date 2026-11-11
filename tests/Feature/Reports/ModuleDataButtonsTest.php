<?php

namespace Tests\Feature\Reports;

use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Filament\Admin\Resources\Client\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Credits\Loans\Pages\ListLoans;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\ListArticles;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Los módulos ya no exportan ni importan por su cuenta: cada listado tiene un botón
 * que lleva a la pantalla única, con el modelo ya elegido.
 */
class ModuleDataButtonsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_los_listados_tienen_el_boton_y_ya_no_las_acciones_sueltas(): void
    {
        $paginas = [
            [ListInflows::class, 'inflows'],
            [ListCustomers::class, 'customers'],
            [ListLoans::class, 'loans'],
            [ListArticles::class, 'articles'],
        ];

        foreach ($paginas as [$pagina, $modelo]) {
            $componente = Livewire::test($pagina)->assertOk();

            // El botón existe y apunta a la tarjeta de ese modelo.
            $componente->assertActionExists('datos');

            $accion = $componente->instance()->getAction('datos');
            $this->assertStringContainsString(
                DataTransfer::getUrl(['modelo' => $modelo]),
                (string) $accion->getUrl(),
                "El botón de {$pagina} no apunta a {$modelo}",
            );

            // Y no queda ninguna acción de exportar o importar suelta.
            $nombres = collect($componente->instance()->getCachedHeaderActions())->map(fn ($a) => $a->getName());

            $this->assertFalse(
                $nombres->contains(fn (string $nombre): bool => str_contains($nombre, 'export') || str_contains($nombre, 'import')),
                "Quedaron acciones de export/import en {$pagina}: " . $nombres->implode(', '),
            );
        }
    }

    public function test_la_ruta_del_boton_abre_la_tarjeta_del_modelo(): void
    {
        Customer::factory()->create();

        Livewire::test(DataTransfer::class, ['modelo' => 'customers'])
            ->assertOk()
            ->assertSee('Clientes')
            ->assertSee('Exportar')
            ->assertSee('Importar');
    }
}
