<?php

namespace Tests\Unit\Credits;

use App\Models\Bank;
use App\Models\User;
use App\Services\RegisterPaymentService;

/**
 * Cobros: quién cobró, con qué banco y fecha, y cuándo se cierra el crédito.
 */
class PaymentTest extends CreditTestCase
{
    public function test_un_pago_guarda_quien_lo_cobro(): void
    {
        $usuario = User::query()->firstOrFail();
        $this->actingAs($usuario);

        $credito = $this->makeCredit([['amount' => 100]]);

        $pago = app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 100,
            paymentMethod: 'cash',
            receiptNumber: 'REC-001',
        );

        $this->assertSame($usuario->id, $pago->user_id);
        $this->assertDatabaseHas('payment_histories', [
            'id' => $pago->id,
            'user_id' => $usuario->id,
        ]);
    }

    public function test_un_pago_respeta_la_fecha_y_el_banco(): void
    {
        $credito = $this->makeCredit([['amount' => 100]]);
        $banco = Bank::query()->create(['name' => 'Banco de prueba']);

        $pago = app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 100,
            paymentMethod: 'bank_transfer',
            receiptNumber: 'REC-002',
            bankId: $banco->id,
            paymentDate: '2026-10-15',
        );

        $this->assertSame($banco->id, $pago->bank_id);
        $this->assertSame('2026-10-15', $pago->payment_date->format('Y-m-d'));
    }

    public function test_el_credito_no_se_cierra_si_queda_una_cuota_atrasada(): void
    {
        // Una cuota 'late' con saldo sigue siendo una deuda: el crédito no puede
        // cerrarse aunque no queden cuotas 'pending'.
        $credito = $this->makeCredit([
            ['amount' => 100],
            ['amount' => 100, 'status' => 'late', 'due' => today()->subMonth()->toDateString()],
        ]);

        app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 100,
            paymentMethod: 'cash',
            receiptNumber: 'REC-003',
        );

        $this->assertSame('active', $credito->fresh()->status);
        $this->assertGreaterThan(0, (float) $credito->fresh()->pending_balance);
    }

    public function test_el_credito_se_cierra_cuando_ya_no_queda_nada_impago(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 100],
            ['amount' => 100],
        ]);

        app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 200,
            paymentMethod: 'cash',
            receiptNumber: 'REC-004',
        );

        $this->assertSame('completed', $credito->fresh()->status);
        $this->assertSame('0.00', $credito->fresh()->pending_balance);
    }

    public function test_un_pago_parcial_deja_la_cuota_pendiente_con_su_saldo(): void
    {
        $credito = $this->makeCredit([['amount' => 100]]);

        app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 40,
            paymentMethod: 'cash',
            receiptNumber: 'REC-005',
        );

        $cuota = $credito->installments()->first();

        $this->assertSame('pending', $cuota->status);
        $this->assertSame('60.00', $cuota->remaining_balance);
        $this->assertSame('60.00', $credito->fresh()->pending_balance);
    }
}
