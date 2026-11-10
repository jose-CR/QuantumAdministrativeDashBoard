<?php

namespace Tests\Feature\Credits;

use App\Filament\Admin\Resources\Client\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Credits\Installments\Pages\ListInstallments;
use App\Filament\Admin\Resources\Credits\Loans\Pages\ListLoans;
use App\Filament\Admin\Resources\Credits\PaymentHistories\Pages\ListPaymentHistories;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las tres pantallas de créditos tienen que abrir con los estados reales de la
 * base: los mapas de estado y las columnas se evalúan al renderizar.
 */
class CreditScreensTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_las_pantallas_de_creditos_responden(): void
    {
        foreach ([
            ListLoans::class,
            ListInstallments::class,
            ListPaymentHistories::class,
        ] as $page) {
            Livewire::test($page)->assertOk();
        }
    }

    public function test_la_pantalla_de_clientes_monta_la_accion_de_refinanciar(): void
    {
        // La acción se arma al renderizar la tabla: acá se cae si le falta un
        // import o si el esquema del modal quedó mal armado.
        Livewire::test(ListCustomers::class)->assertOk();
    }
}
