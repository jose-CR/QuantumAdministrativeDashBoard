<?php

namespace Tests\Feature\Credits;

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

        $this->actingAs(User::query()->firstOrFail());

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
}
