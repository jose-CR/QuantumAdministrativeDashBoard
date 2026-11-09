<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El panel ya no dibuja el sidebar: la navegación vive en el lanzador de apps.
 * Estas pruebas entran por la ruta real (con el layout completo), no por
 * Livewire, que se salta el layout.
 */
class PanelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_el_lanzador_carga_sin_sidebar_y_con_todas_las_apps(): void
    {
        $response = $this->get('/admin/apps');

        $response->assertOk();

        // Las tarjetas se siguen armando desde la navegación del panel.
        $response->assertSee('Créditos');
        $response->assertSee('Administración');
        $response->assertSee('Inventario');

        // Sin sidebar no hay menú lateral en el layout.
        $this->assertStringNotContainsString('fi-sidebar"', $response->getContent());
    }

    public function test_el_escritorio_sigue_respondiendo(): void
    {
        $this->get('/admin')->assertOk();
    }
}
