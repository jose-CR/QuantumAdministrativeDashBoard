<?php

namespace Tests\Unit\Support;

use App\Models\Customer;
use App\Utils\Filament\FilamentSearch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La búsqueda de los selects se hace en la base: varias columnas a la vez, sin
 * distinguir mayúsculas y con un tope de resultados.
 */
class FilamentSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_busca_en_varias_columnas_a_la_vez(): void
    {
        $ana = Customer::factory()->create(['full_name' => 'Ana García', 'document_number' => '0451-111111-101-1']);
        Customer::factory()->create(['full_name' => 'Beto Ruiz', 'document_number' => '0452-222222-102-2']);

        $porNombre = FilamentSearch::options(Customer::query(), 'garcía', ['full_name', 'document_number']);
        $porDocumento = FilamentSearch::options(Customer::query(), '0451-1111', ['full_name', 'document_number']);

        $etiqueta = 'Ana García - 0451-111111-101-1';

        $this->assertSame([$ana->id => $etiqueta], $porNombre);
        $this->assertSame([$ana->id => $etiqueta], $porDocumento);
    }

    public function test_no_distingue_mayusculas(): void
    {
        $cliente = Customer::factory()->create(['full_name' => 'María López']);

        $this->assertArrayHasKey($cliente->id, FilamentSearch::options(Customer::query(), 'MARÍA', ['full_name']));
        $this->assertArrayHasKey($cliente->id, FilamentSearch::options(Customer::query(), 'maría', ['full_name']));
    }

    public function test_sin_texto_devuelve_la_lista_y_respeta_el_tope(): void
    {
        Customer::factory()->count(8)->create();

        $this->assertCount(5, FilamentSearch::options(Customer::query(), null, ['full_name'], ['full_name'], limit: 5));
        $this->assertCount(3, FilamentSearch::options(Customer::query(), '', ['full_name'], ['full_name'], limit: 3));
    }

    public function test_busca_por_columnas_de_una_relacion(): void
    {
        $cliente = Customer::factory()->create(['full_name' => 'Cliente Relación']);
        $credito = \App\Models\Credit::factory()->create(['customer_id' => $cliente->id]);
        \App\Models\Credit::factory()->create();

        $opciones = FilamentSearch::options(
            \App\Models\Credit::query(),
            'relación',
            [],
            fn ($registro): string => "#{$registro->id}",
            relations: ['client' => ['full_name']],
        );

        $this->assertArrayHasKey($credito->id, $opciones);
        $this->assertCount(1, $opciones);
    }

    public function test_puede_buscar_un_id_como_texto(): void
    {
        $credito = \App\Models\Credit::factory()->create();
        \App\Models\Credit::factory()->count(3)->create();

        $opciones = FilamentSearch::options(
            \App\Models\Credit::query(),
            (string) $credito->id,
            ['id'],
            fn ($registro): string => "#{$registro->id}",
            castColumns: ['id'],
        );

        $this->assertArrayHasKey($credito->id, $opciones);
    }

    public function test_la_etiqueta_junta_las_columnas(): void
    {
        $cliente = new Customer(['full_name' => 'Ana García', 'document_number' => '0451-1']);

        $this->assertSame('Ana García - 0451-1', FilamentSearch::label($cliente, ['full_name', 'document_number']));
        $this->assertNull(FilamentSearch::label(null, ['full_name']));
    }
}
