<?php

namespace Tests\Feature\Credits;

use App\Filament\Admin\Resources\Credits\PaymentHistories\Pages\EditPaymentHistories;
use App\Models\Credit;
use App\Models\Installment;
use App\Models\PaymentHistory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Un pago cargado con el monto equivocado se corrige desde la pantalla: las cuotas se
 * recalculan y la corrección queda anotada en el historial.
 */
class PaymentCorrectionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    /**
     * Crédito de 2 cuotas de 110, con un pago cargado por 110 (cuando eran 220).
     *
     * @return array{0: Credit, 1: PaymentHistory}
     */
    private function creditoConPagoMalCargado(): array
    {
        $credito = Credit::factory()->create([
            'start_date' => '2026-01-10',
            'payment_day' => 15,
            'installments' => 2,
            'installment_amount' => 110,
            'total_amount' => 220,
            'financed_amount' => 220,
            'status' => 'active',
        ]);

        $credito->installments()->create([
            'number' => 1,
            'amount' => 110,
            'remaining_balance' => 110,
            'due_date' => '2026-01-15',
            'status' => 'pending',
        ]);

        $credito->installments()->create([
            'number' => 2,
            'amount' => 110,
            'remaining_balance' => 110,
            'due_date' => '2026-02-15',
            'status' => 'pending',
        ]);

        $pago = PaymentHistory::create([
            'credit_id' => $credito->id,
            'amount' => 110,
            'previous_balance' => 220,
            'new_balance' => 110,
            'payment_method' => 'cash',
            'payment_date' => '2026-08-21',
            'receipt_number' => 'REC-1',
        ]);

        // El pago se carga y se aplica como lo hace la pantalla de cobro.
        app(\App\Services\Credits\RecalculateCreditService::class)->execute($credito);

        return [$credito->fresh(), $pago->fresh()];
    }

    public function test_corregir_el_monto_recalcula_las_cuotas(): void
    {
        [$credito, $pago] = $this->creditoConPagoMalCargado();

        // Con 110 solo se cubre la primera cuota.
        $this->assertSame(1, $credito->installments()->where('status', 'paid')->count());

        Livewire::test(EditPaymentHistories::class, ['record' => $pago->getRouteKey()])
            ->fillForm(['amount' => 220])
            ->call('save')
            ->assertHasNoFormErrors();

        $credito = $credito->fresh();

        $this->assertSame(2, $credito->installments()->where('status', 'paid')->count());
        $this->assertSame('0.00', $credito->pending_balance);
    }

    public function test_la_correccion_queda_anotada_en_el_pago(): void
    {
        [$credito, $pago] = $this->creditoConPagoMalCargado();

        Livewire::test(EditPaymentHistories::class, ['record' => $pago->getRouteKey()])
            ->fillForm(['amount' => 220])
            ->call('save')
            ->assertHasNoFormErrors();

        $pago = $pago->fresh();

        $this->assertSame('220.00', $pago->amount);
        $this->assertStringContainsString('110.00', $pago->notes);
        $this->assertStringContainsString('220.00', $pago->notes);
        // Sin el '<p></p>' vacío del editor delante.
        $this->assertStringStartsWith('Monto corregido', $pago->notes);
    }

    public function test_si_no_cambia_el_monto_no_agrega_nota(): void
    {
        [$credito, $pago] = $this->creditoConPagoMalCargado();

        Livewire::test(EditPaymentHistories::class, ['record' => $pago->getRouteKey()])
            ->fillForm(['receipt_number' => 'REC-1-BIS'])
            ->call('save')
            ->assertHasNoFormErrors();

        // El editor de texto devuelve '<p></p>' cuando está vacío.
        $this->assertTrue(blank(strip_tags((string) $pago->fresh()->notes)));
    }
}
