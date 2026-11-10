<?php

namespace Tests\Unit\Credits;

use App\Services\RegisterPaymentService;

/**
 * Corregir un pago no puede dejar el crédito descuadrado: los pagos son la
 * fuente de verdad y las cuotas se rehacen a partir de ellos.
 */
class PaymentCorrectionTest extends CreditTestCase
{
    public function test_borrar_un_pago_devuelve_las_cuotas_a_como_estaban(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 50],
            ['amount' => 50],
        ]);

        $pago = app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 100,
            paymentMethod: 'cash',
            receiptNumber: 'REC-100',
        );

        $this->assertSame('completed', $credito->fresh()->status);

        $pago->delete();

        $credito->refresh();

        $this->assertSame('active', $credito->status);
        $this->assertSame('100.00', $credito->pending_balance);
        $this->assertSame(
            ['pending', 'pending'],
            $credito->installments()->orderBy('number')->pluck('status')->all()
        );
        $this->assertSame(
            ['50.00', '50.00'],
            $credito->installments()->orderBy('number')->pluck('remaining_balance')->all()
        );
    }

    public function test_editar_el_monto_de_un_pago_rehace_las_cuotas(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 50],
            ['amount' => 50],
        ]);

        $pago = app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 100,
            paymentMethod: 'cash',
            receiptNumber: 'REC-101',
        );

        $pago->update(['amount' => 50]);

        $credito->refresh();

        $this->assertSame('active', $credito->status);
        $this->assertSame('50.00', $credito->pending_balance);
        $this->assertSame(
            ['paid', 'pending'],
            $credito->installments()->orderBy('number')->pluck('status')->all()
        );
        $this->assertSame(
            ['0.00', '50.00'],
            $credito->installments()->orderBy('number')->pluck('remaining_balance')->all()
        );
    }

    public function test_las_cuotas_vencidas_quedan_atrasadas_al_recalcular(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 50, 'due' => today()->subMonth()->toDateString()],
            ['amount' => 50],
        ]);

        $pago = app(RegisterPaymentService::class)->execute(
            credit: $credito,
            amount: 100,
            paymentMethod: 'cash',
            receiptNumber: 'REC-102',
        );

        $pago->delete();

        $this->assertSame(
            ['late', 'pending'],
            $credito->installments()->orderBy('number')->pluck('status')->all()
        );
    }

    public function test_borrar_una_cuota_recalcula_el_saldo_del_credito(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 50],
            ['amount' => 50],
        ]);

        $cuota = $credito->installments()->orderBy('number')->firstOrFail();
        $cuota->delete();

        $credito->refresh();

        $this->assertSame('50.00', $credito->pending_balance);
    }
}
