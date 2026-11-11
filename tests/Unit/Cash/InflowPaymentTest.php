<?php

namespace Tests\Unit\Cash;

use App\Models\Inflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * En una entrada de caja el estado se deriva de lo cobrado: no se elige a mano.
 * Antes se podía marcar "parcial" sin decir cuánto entró y ningún reporte podía
 * calcular el saldo.
 */
class InflowPaymentTest extends TestCase
{
    use DatabaseTransactions;

    private function entrada(array $atributos = []): Inflow
    {
        return Inflow::factory()->create(array_merge([
            'amount' => 100,
            'amount_paid' => 0,
            'payment_status' => 'pending',
        ], $atributos));
    }

    public function test_sin_cobrar_queda_pendiente(): void
    {
        $entrada = $this->entrada(['amount_paid' => 0]);

        $this->assertSame('pending', $entrada->payment_status);
        $this->assertSame(100.0, $entrada->receivable);
    }

    public function test_cobrado_entero_queda_pagada(): void
    {
        $entrada = $this->entrada(['amount_paid' => 100]);

        $this->assertSame('paid', $entrada->payment_status);
        $this->assertSame(0.0, $entrada->receivable);
    }

    public function test_cobrado_parcial_queda_en_parcial_con_su_saldo(): void
    {
        $entrada = $this->entrada(['amount_paid' => 40]);

        $this->assertSame('partial', $entrada->payment_status);
        $this->assertSame(60.0, $entrada->receivable);
    }

    public function test_el_estado_viejo_pagado_sin_monto_se_completa(): void
    {
        // Datos de antes de la columna: decían "pagado" y no tenían monto.
        $entrada = $this->entrada([
            'amount' => 250,
            'amount_paid' => 0,
            'payment_status' => 'paid',
        ]);

        $this->assertSame('paid', $entrada->payment_status);
        $this->assertSame(250.0, (float) $entrada->amount_paid);
    }

    public function test_no_se_puede_cobrar_mas_que_el_monto(): void
    {
        $this->expectException(ValidationException::class);

        $this->entrada(['amount' => 100, 'amount_paid' => 150]);
    }

    public function test_no_se_puede_cobrar_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->entrada(['amount' => 100, 'amount_paid' => -10]);
    }

    public function test_una_entrada_sin_monto_no_se_guarda(): void
    {
        $this->expectException(ValidationException::class);

        $this->entrada(['amount' => 0, 'amount_paid' => 0]);
    }

    public function test_al_cambiar_lo_cobrado_cambia_el_estado(): void
    {
        $entrada = $this->entrada(['amount' => 100, 'amount_paid' => 0]);

        $entrada->update(['amount_paid' => 100]);

        $this->assertSame('paid', $entrada->fresh()->payment_status);

        $entrada->update(['amount_paid' => 30]);

        $this->assertSame('partial', $entrada->fresh()->payment_status);
    }
}
