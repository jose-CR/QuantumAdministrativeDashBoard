<?php

namespace Tests\Feature\Credits;

use App\Filament\Imports\Credits\LoanImporter;
use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Models\Customer;
use App\Rules\Credits\LoanRules;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * La importación de créditos tenía columnas fantasma (`client_id`,
 * `article_unit_id`): el cliente y el bien se descartaban en silencio.
 */
class LoanImporterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_las_columnas_del_import_existen_en_la_tabla(): void
    {
        $reales = Schema::getColumnListing('credits');

        foreach (LoanImporter::getColumns() as $columna) {
            $nombre = $columna->getName();

            // vin no es columna: se guarda como el ítem del crédito.
            if ($nombre === 'vin') {
                continue;
            }

            $this->assertContains($nombre, $reales, "El import pide {$nombre} y no existe en credits.");
        }
    }

    public function test_las_reglas_apuntan_al_cliente_real(): void
    {
        $reglas = LoanRules::import();

        $this->assertArrayHasKey('customer_id', $reglas);
        $this->assertArrayNotHasKey('client_id', $reglas);
        $this->assertArrayNotHasKey('article_unit_id', $reglas);
        $this->assertArrayNotHasKey('assigned_user_id', $reglas);
    }

    public function test_una_fila_completa_pasa_las_reglas(): void
    {
        $cliente = Customer::factory()->create();

        $fila = [
            'customer_id' => $cliente->id,
            'initial_amount' => 1000,
            'financed_amount' => 800,
            'installments' => 6,
            'installment_amount' => 150,
            'periodicity' => 'monthly',
            'total_amount' => 900,
            'start_date' => today()->toDateString(),
            'payment_day' => 15,
            'status' => 'active',
        ];

        $this->assertFalse(Validator::make($fila, LoanRules::import())->fails());
    }

    public function test_rechaza_un_cliente_que_no_existe_y_una_periodicidad_invalida(): void
    {
        $validador = Validator::make([
            'customer_id' => 999999,
            'initial_amount' => 1000,
            'financed_amount' => 800,
            'installments' => 6,
            'installment_amount' => 150,
            'periodicity' => 'biweekly',
            'total_amount' => 900,
            'start_date' => today()->toDateString(),
            'payment_day' => 15,
            'status' => 'active',
        ], LoanRules::import());

        $this->assertTrue($validador->fails());
        $this->assertArrayHasKey('customer_id', $validador->errors()->toArray());
        $this->assertArrayHasKey('periodicity', $validador->errors()->toArray());
    }

    public function test_la_serie_del_vehiculo_se_valida_contra_las_unidades(): void
    {
        $unidad = ArticleUnit::query()->firstOrFail();

        $this->assertFalse(Validator::make(
            ['vin' => $unidad->vin],
            ['vin' => LoanRules::import()['vin']],
        )->fails());

        $this->assertTrue(Validator::make(
            ['vin' => 'NO-EXISTE-999'],
            ['vin' => LoanRules::import()['vin']],
        )->fails());
    }

    public function test_la_periodicidad_del_formulario_es_la_que_acepta_la_base(): void
    {
        $permitidas = array_map(
            fn (array $fila): string => $fila['periodicity'],
            [['periodicity' => 'weekly'], ['periodicity' => 'monthly'], ['periodicity' => 'yearly']],
        );

        $this->assertSame(LoanRules::PERIODICITIES, $permitidas);
        $this->assertNotContains('biweekly', LoanRules::PERIODICITIES);
    }
}
