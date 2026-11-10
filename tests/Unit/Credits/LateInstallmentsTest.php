<?php

namespace Tests\Unit\Credits;

use App\Console\Commands\Credits\MarkLateInstallmentsCommand;

/**
 * El estado 'late' tiene que escribirse en algún momento: si no, una cuota
 * vencida se queda 'pending' y el panel la muestra al día.
 */
class LateInstallmentsTest extends CreditTestCase
{
    public function test_el_comando_marca_como_atrasadas_las_vencidas(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 50, 'due' => today()->subDays(3)->toDateString()],
            ['amount' => 50, 'due' => today()->addDays(10)->toDateString()],
        ]);

        $this->artisan(MarkLateInstallmentsCommand::class)->assertSuccessful();

        $cuotas = $credito->installments()->orderBy('number')->get();

        $this->assertSame('late', $cuotas[0]->status);
        $this->assertSame('pending', $cuotas[1]->status);
    }

    public function test_no_toca_las_pagadas(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 50, 'due' => today()->subMonth()->toDateString(), 'status' => 'paid'],
        ]);

        $this->artisan(MarkLateInstallmentsCommand::class)->assertSuccessful();

        $this->assertSame('paid', $credito->installments()->firstOrFail()->status);
    }

    public function test_no_toca_las_cuotas_sin_saldo(): void
    {
        $credito = $this->makeCredit([
            ['amount' => 0, 'due' => today()->subMonth()->toDateString()],
        ]);

        $this->artisan(MarkLateInstallmentsCommand::class)->assertSuccessful();

        $this->assertSame('pending', $credito->installments()->firstOrFail()->status);
    }

    public function test_no_toca_los_creditos_cerrados(): void
    {
        // Un crédito completado o refinanciado conserva sus cuotas como están.
        $credito = $this->makeCredit([
            ['amount' => 50, 'due' => today()->subMonth()->toDateString()],
        ]);

        $credito->update(['status' => 'refinanced']);

        $this->artisan(MarkLateInstallmentsCommand::class)->assertSuccessful();

        $this->assertSame('pending', $credito->installments()->firstOrFail()->status);
    }
}
