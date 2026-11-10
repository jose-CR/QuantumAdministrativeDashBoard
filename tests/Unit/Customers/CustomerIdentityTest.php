<?php

namespace Tests\Unit\Customers;

use App\Models\Customer;
use App\Support\ElSalvadorCatalogo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * La identidad de un cliente es su documento, no su nombre: dos personas pueden
 * llamarse igual, pero no pueden tener el mismo tipo y número de documento.
 */
class CustomerIdentityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dos_clientes_pueden_llamarse_igual(): void
    {
        // Antes `full_name` era UNIQUE y el segundo revienta con error de base.
        $uno = Customer::factory()->create(['full_name' => 'Juan Pérez']);
        $dos = Customer::factory()->create(['full_name' => 'Juan Pérez']);

        $this->assertNotSame($uno->id, $dos->id);
        $this->assertSame(2, Customer::query()->where('full_name', 'Juan Pérez')->count());
    }

    public function test_el_mismo_numero_con_otro_tipo_es_otro_cliente(): void
    {
        // El NIT de una empresa puede coincidir con el pasaporte de una persona.
        $persona = Customer::factory()->create([
            'document_type' => 'PASSPORT',
            'document_number' => '123456789',
        ]);

        $empresa = Customer::factory()->create([
            'document_type' => 'NIT',
            'document_number' => '123456789',
        ]);

        $this->assertNotSame($persona->id, $empresa->id);
    }

    public function test_el_mismo_tipo_y_numero_se_rechaza(): void
    {
        Customer::factory()->create([
            'document_type' => 'DUI',
            'document_number' => '01234567-8',
        ]);

        $this->expectException(ValidationException::class);

        Customer::factory()->create([
            'document_type' => 'DUI',
            'document_number' => '01234567-8',
        ]);
    }

    public function test_editar_sin_cambiar_el_documento_no_choca_consigo_mismo(): void
    {
        $cliente = Customer::factory()->create([
            'document_type' => 'DUI',
            'document_number' => '04567890-1',
        ]);

        $cliente->update(['phone_primary' => '7777-7777']);

        $this->assertSame('04567890-1', $cliente->fresh()->document_number);
    }

    public function test_un_tipo_de_documento_inventado_se_rechaza(): void
    {
        // El import podía escribir cualquier cosa; la base no tiene CHECK.
        $this->expectException(ValidationException::class);

        Customer::factory()->create(['document_type' => 'CURP']);
    }

    public function test_un_municipio_de_otro_departamento_se_rechaza(): void
    {
        $departamentos = array_keys(ElSalvadorCatalogo::departments());

        [$primero, $segundo] = [$departamentos[0], $departamentos[1]];

        $municipioAjeno = array_key_first(
            ElSalvadorCatalogo::municipalities($segundo)
        );

        $distritoAjeno = array_key_first(
            ElSalvadorCatalogo::districts($municipioAjeno)
        );

        $this->expectException(ValidationException::class);

        Customer::factory()->create([
            'department' => $primero,
            'municipality' => $municipioAjeno,
            'district' => $distritoAjeno,
        ]);
    }

    public function test_un_departamento_inventado_se_rechaza(): void
    {
        $this->expectException(ValidationException::class);

        Customer::factory()->create(['department' => '99']);
    }

    public function test_los_clientes_que_ya_existen_siguen_siendo_validos(): void
    {
        // La validación no puede romper datos viejos: se guardan sin cambios.
        $cliente = Customer::query()->firstOrFail();

        $cliente->update(['updated_at' => now()]);

        $this->assertTrue($cliente->exists);
    }
}
