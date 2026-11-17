<?php

namespace Tests\Unit\Credits;

use App\Models\Credit;
use App\Services\Credits\InstallmentSyncService;
use App\Services\InstallmentGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Las cuotas vencen el día de pago pactado, no el día en que se abrió el crédito.
 */
class InstallmentDueDateTest extends TestCase
{
    use DatabaseTransactions;

    private function credito(array $atributos = []): Credit
    {
        return Credit::factory()->create([
            'start_date' => '2026-11-10',
            'payment_day' => 27,
            'periodicity' => 'monthly',
            'installments' => 3,
            'installment_amount' => 100,
            'total_amount' => 300,
            'financed_amount' => 300,
            ...$atributos,
        ]);
    }

    public function test_la_primera_cuota_vence_el_dia_de_pago_del_mes_siguiente(): void
    {
        $credito = $this->credito();

        app(InstallmentGeneratorService::class)->generate($credito);

        $fechas = $credito->installments()->orderBy('number')->pluck('due_date')
            ->map(fn ($fecha) => $fecha->toDateString())
            ->all();

        // Inicio 10/11 y día de pago 27: no el 10 de cada mes.
        $this->assertSame(['2026-12-27', '2027-01-27', '2027-02-27'], $fechas);
    }

    public function test_el_dia_31_cae_en_el_ultimo_dia_del_mes_corto(): void
    {
        $credito = $this->credito([
            'start_date' => '2026-12-15',
            'payment_day' => 31,
            'installments' => 3,
            'total_amount' => 300,
        ]);

        app(InstallmentGeneratorService::class)->generate($credito);

        $fechas = $credito->installments()->orderBy('number')->pluck('due_date')
            ->map(fn ($fecha) => $fecha->toDateString())
            ->all();

        // Enero 31, febrero 28 (2027 no es bisiesto) y marzo 31.
        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], $fechas);
    }

    public function test_semanal_cuenta_desde_el_inicio(): void
    {
        $credito = $this->credito([
            'periodicity' => 'weekly',
            'payment_day' => 27,
            'installments' => 2,
        ]);

        app(InstallmentGeneratorService::class)->generate($credito);

        $fechas = $credito->installments()->orderBy('number')->pluck('due_date')
            ->map(fn ($fecha) => $fecha->toDateString())
            ->all();

        $this->assertSame(['2026-11-17', '2026-11-24'], $fechas);
    }

    public function test_al_agregar_cuotas_despues_siguen_el_dia_de_pago(): void
    {
        $credito = $this->credito();
        app(InstallmentGeneratorService::class)->generate($credito);

        $credito->update(['installments' => 4, 'total_amount' => 400]);
        app(InstallmentSyncService::class)->sync($credito->fresh());

        $cuarta = $credito->installments()->where('number', 4)->firstOrFail();

        $this->assertSame('2027-03-27', $cuarta->due_date->toDateString());
    }

    public function test_sin_dia_de_pago_usa_el_dia_del_inicio(): void
    {
        // payment_day es NOT NULL en la base: se prueba sobre el modelo sin guardar.
        $credito = new Credit([
            'start_date' => '2026-11-10',
            'periodicity' => 'monthly',
            'payment_day' => null,
        ]);

        $this->assertSame('2026-12-10', $credito->dueDateFor(1)->toDateString());
    }
}
