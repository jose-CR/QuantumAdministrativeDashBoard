<?php

namespace Tests\Unit\Credits;

use App\Services\LoanCalculatorService;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * El interés puede salir de la cuota que se cobra ('auto') o pactarse como tasa
 * fija ('fixed'). Con tasa fija, la cuota y el total son el resultado.
 */
class LoanCalculatorTest extends TestCase
{
    private function calculate(array $data): array
    {
        return app(LoanCalculatorService::class)->calculate(array_merge([
            'initial_amount' => 1000,
            'down_payment' => 0,
            'installments' => 10,
        ], $data));
    }

    public function test_sin_interes_fijo_la_tasa_sale_de_la_cuota(): void
    {
        $resultado = $this->calculate([
            'installment_amount' => 120,
            'interest_type' => 'auto',
        ]);

        $this->assertSame('auto', $resultado['interest_type']);
        $this->assertSame(1200.0, $resultado['total_amount']);
        $this->assertSame(200.0, $resultado['total_interest']);
        $this->assertSame(2.0, $resultado['interest_rate']);
        $this->assertSame(120.0, $resultado['installment_amount']);
    }

    public function test_con_interes_fijo_la_tasa_manda_sobre_la_cuota(): void
    {
        // La cuota que venga del formulario se ignora: manda el 2% pactado.
        $resultado = $this->calculate([
            'installment_amount' => 999,
            'interest_type' => 'fixed',
            'interest_rate' => 2,
        ]);

        $this->assertSame('fixed', $resultado['interest_type']);
        $this->assertSame(1200.0, $resultado['total_amount']);
        $this->assertSame(200.0, $resultado['total_interest']);
        $this->assertSame(2.0, $resultado['interest_rate']);
        $this->assertSame(120.0, $resultado['installment_amount']);
    }

    public function test_el_mismo_credito_con_otra_tasa_da_otro_precio(): void
    {
        $barato = $this->calculate([
            'interest_type' => 'fixed',
            'interest_rate' => 1.5,
        ]);

        $caro = $this->calculate([
            'interest_type' => 'fixed',
            'interest_rate' => 3,
        ]);

        $this->assertSame(1150.0, $barato['total_amount']);
        $this->assertSame(1300.0, $caro['total_amount']);
        $this->assertSame(115.0, $barato['installment_amount']);
        $this->assertSame(130.0, $caro['installment_amount']);
    }

    public function test_el_pago_inicial_baja_lo_financiado_y_el_interes(): void
    {
        $resultado = $this->calculate([
            'initial_amount' => 1000,
            'down_payment' => 200,
            'installments' => 4,
            'interest_type' => 'fixed',
            'interest_rate' => 2,
        ]);

        $this->assertSame(800.0, $resultado['financed_amount']);
        $this->assertSame(864.0, $resultado['total_amount']);
        $this->assertSame(64.0, $resultado['total_interest']);
        $this->assertSame(216.0, $resultado['installment_amount']);
    }

    public function test_la_cuota_se_redondea_hacia_abajo_para_que_la_ultima_absorba_el_resto(): void
    {
        $resultado = $this->calculate([
            'installments' => 3,
            'interest_type' => 'fixed',
            'interest_rate' => 5,
        ]);

        // 1000 * (1 + 0.05 * 3) = 1150
        $this->assertSame(1150.0, $resultado['total_amount']);
        $this->assertSame(383.33, $resultado['installment_amount']);

        // La última cuota es la que cierra la cuenta.
        $this->assertSame(
            1150.0,
            round($resultado['installment_amount'] * 2 + (1150 - $resultado['installment_amount'] * 2), 2)
        );
        $this->assertSame(383.34, round(1150 - $resultado['installment_amount'] * 2, 2));
    }

    public function test_una_tasa_absurda_se_rechaza(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculate([
            'interest_type' => 'fixed',
            'interest_rate' => 50,
        ]);
    }

    public function test_una_tasa_negativa_se_rechaza(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculate([
            'interest_type' => 'fixed',
            'interest_rate' => -1,
        ]);
    }

    public function test_con_interes_fijo_hace_falta_saldo_que_financiar(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculate([
            'initial_amount' => 1000,
            'down_payment' => 1000,
            'interest_type' => 'fixed',
            'interest_rate' => 2,
        ]);
    }
}
