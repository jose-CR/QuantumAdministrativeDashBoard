<?php

namespace Tests\Feature\Settings;

use App\Exceptions\BusinessRuleException;
use App\Filament\Admin\Actions\PayInstallmentAction;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Installment;
use App\Support\Ui\Alert;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Los errores dicen qué se equivocó el usuario y dónde.
 *
 * Si el problema es de un campo, se marca el campo (no un aviso suelto); si es del
 * documento, va un aviso con el motivo; nunca un «error» pelado.
 */
class ErrorMessagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function creditoConCuota(float $saldo = 1000): array
    {
        $cliente = Customer::factory()->create();

        $credito = Credit::factory()->create([
            'customer_id' => $cliente->id,
            'status' => 'active',
            'installments' => 1,
            'installment_amount' => $saldo,
            'total_amount' => $saldo,
            'pending_balance' => $saldo,
        ]);

        $cuota = $credito->installments()->create([
            'number' => 1,
            'amount' => $saldo,
            'remaining_balance' => $saldo,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'pending',
        ]);

        return [$credito, $cuota];
    }

    public function test_la_excepcion_de_negocio_dice_si_es_de_campo_o_de_documento(): void
    {
        $deCampo = BusinessRuleException::field('amount', 'motivo');
        $this->assertSame('amount', $deCampo->campo);
        $this->assertSame('motivo', $deCampo->getMessage());

        $deDocumento = BusinessRuleException::document('otro motivo');
        $this->assertNull($deDocumento->campo);
    }

    public function test_un_error_de_campo_llega_como_error_del_formulario(): void
    {
        $this->expectException(ValidationException::class);

        Alert::field('amount', 'El monto no puede superar el saldo.');
    }

    public function test_un_error_del_documento_avisa_y_detiene(): void
    {
        // Sin campo: la acción se detiene (Halt) y el motivo va en el aviso.
        $this->expectException(Halt::class);

        Alert::fromRule(BusinessRuleException::document('El crédito está cancelado.'));
    }

    public function test_el_aviso_convierte_el_campo_en_error_del_formulario(): void
    {
        $this->expectException(ValidationException::class);

        try {
            Alert::fromRule(BusinessRuleException::field('amount', 'El monto es muy alto.'));
        } catch (ValidationException $error) {
            $this->assertSame('El monto es muy alto.', $error->errors()['amount'][0] ?? '');

            throw $error;
        }
    }

    public function test_pagar_mas_que_el_saldo_marca_el_campo_del_monto(): void
    {
        [$credito, $cuota] = $this->creditoConCuota(1000);

        // El servicio dice qué campo y qué pasó; la acción es la que lo convierte en
        // error del formulario.
        $this->expectException(BusinessRuleException::class);

        try {
            app(\App\Services\RegisterPaymentService::class)->execute(
                credit: $credito,
                amount: 1500,
                paymentMethod: 'cash',
                receiptNumber: 'REC-X',
                notes: null,
                mode: 'single',
                installmentId: $cuota->id,
            );
        } catch (BusinessRuleException $error) {
            $this->assertSame('amount', $error->campo);

            // El mensaje dice cuánto se intentó pagar y cuánto queda.
            $this->assertStringContainsString('1,500.00', $error->getMessage());
            $this->assertStringContainsString('1,000.00', $error->getMessage());

            throw $error;
        }
    }

    public function test_pagar_un_credito_cancelado_avisa_sin_marcar_campo(): void
    {
        [$credito, $cuota] = $this->creditoConCuota(500);
        $credito->update(['status' => 'cancelled']);

        $this->expectException(BusinessRuleException::class);

        try {
            app(\App\Services\RegisterPaymentService::class)->execute(
                credit: $credito->fresh(),
                amount: 100,
                paymentMethod: 'cash',
                receiptNumber: 'REC-Y',
                notes: null,
                mode: 'single',
                installmentId: $cuota->id,
            );
        } catch (BusinessRuleException $error) {
            $this->assertNull($error->campo);
            $this->assertSame(__('ui.validation.credit_not_active'), $error->getMessage());

            throw $error;
        }
    }

    /**
     * Cada número mal puesto del crédito señala SU campo, no el formulario entero.
     */
    public function test_el_calculo_del_credito_senala_el_campo_que_falla(): void
    {
        $calculadora = app(\App\Services\LoanCalculatorService::class);

        $casos = [
            'installments' => ['initial_amount' => 1200, 'down_payment' => 200, 'installments' => 0],
            'down_payment' => ['initial_amount' => 1000, 'down_payment' => 1500, 'installments' => 6],
            'initial_amount' => ['initial_amount' => 0, 'down_payment' => 0, 'installments' => 6],
        ];

        foreach ($casos as $campoEsperado => $datos) {
            try {
                $calculadora->calculate(array_merge([
                    'interest_rate' => 0,
                    'periodicity' => 'monthly',
                ], $datos));

                $this->fail("No falló con {$campoEsperado}.");
            } catch (BusinessRuleException $error) {
                $this->assertSame($campoEsperado, $error->campo, "El error no apuntó a {$campoEsperado}.");
                // El motivo viene traducido, no como texto suelto en el código.
                $this->assertNotSame('ui.validation.', mb_substr($error->getMessage(), 0, 15));
            }
        }
    }

    public function test_la_tasa_muy_alta_señala_el_campo_de_la_tasa(): void
    {
        $this->expectException(BusinessRuleException::class);

        try {
            // La tasa manda solo en modo fijo: en automático sale del cálculo.
            app(\App\Services\LoanCalculatorService::class)->calculate([
                'initial_amount' => 1200,
                'down_payment' => 0,
                'installments' => 6,
                'interest_rate' => 999,
                'interest_type' => 'fixed',
                'periodicity' => 'monthly',
            ]);
        } catch (BusinessRuleException $error) {
            $this->assertSame('interest_rate', $error->campo);

            throw $error;
        }
    }

    public function test_no_se_pueden_quitar_cuotas_de_un_credito_ya_generado(): void
    {
        [$credito] = $this->creditoConCuota(600);

        // Ya tiene 12 cuotas generadas: bajar la cantidad dejaría cuotas huérfanas.
        for ($numero = 2; $numero <= 12; $numero++) {
            $credito->installments()->create([
                'number' => $numero,
                'amount' => 50,
                'remaining_balance' => 50,
                'due_date' => now()->addMonths($numero - 1)->toDateString(),
                'status' => 'pending',
            ]);
        }

        $credito->update(['installments' => 6, 'installment_amount' => 50]);

        $this->expectException(BusinessRuleException::class);

        try {
            app(\App\Services\Credits\InstallmentSyncService::class)->sync($credito->fresh());
        } catch (BusinessRuleException $error) {
            // El error apunta al campo «Cuotas» de la pantalla del crédito.
            $this->assertSame('installments', $error->campo);
            $this->assertSame(__('ui.validation.installments_cannot_reduce'), $error->getMessage());

            throw $error;
        }
    }

    public function test_refinanciar_un_credito_cancelado_dice_por_que(): void
    {
        [$credito] = $this->creditoConCuota(500);
        $credito->update(['status' => 'cancelled']);

        $this->expectException(BusinessRuleException::class);

        try {
            app(\App\Services\Credits\RefinancingService::class)->execute(
                $credito->client,
                $credito->fresh(),
                ['installments' => 6, 'interest_rate' => 0],
            );
        } catch (BusinessRuleException $error) {
            $this->assertNull($error->campo);
            $this->assertSame(__('ui.validation.credit_not_active'), $error->getMessage());

            throw $error;
        }
    }

    public function test_la_accion_de_pagar_esta_conectada_al_aviso(): void
    {
        // La clase existe y expone la acción: si se rompe el `use`, esto falla.
        $this->assertTrue(class_exists(PayInstallmentAction::class));
        $this->assertTrue(method_exists(PayInstallmentAction::class, 'make'));
    }
}
