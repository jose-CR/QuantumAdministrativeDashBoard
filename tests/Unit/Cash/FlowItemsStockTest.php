<?php

namespace Tests\Unit\Cash;

use App\Models\Article;
use App\Models\Category;
use App\Models\Inflow;
use App\Models\Outflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Las líneas de una entrada o salida mueven inventario: una venta descuenta y una
 * compra suma. Antes la caja y el stock vivían separados.
 */
class FlowItemsStockTest extends TestCase
{
    use DatabaseTransactions;

    private function articulo(int $stock = 10, string $tracking = Article::TRACKING_NONE): Article
    {
        return Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casco abatible',
            'tracking' => $tracking,
            'cash_price' => 25,
            'stock' => $stock,
        ]);
    }

    private function entrada(array $atributos = []): Inflow
    {
        return Inflow::factory()->create(array_merge([
            'amount' => 100,
            'amount_paid' => 100,
        ], $atributos));
    }

    private function salida(array $atributos = []): Outflow
    {
        return Outflow::factory()->create($atributos);
    }

    public function test_una_venta_descuenta_stock(): void
    {
        $articulo = $this->articulo(stock: 10);
        $entrada = $this->entrada();

        $entrada->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 3,
            'price' => 25,
        ]);

        $this->assertSame(7, $articulo->fresh()->stock);
    }

    public function test_una_compra_suma_stock(): void
    {
        $articulo = $this->articulo(stock: 2);
        $salida = $this->salida();

        $salida->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 5,
            'price' => 15,
        ]);

        $this->assertSame(7, $articulo->fresh()->stock);
    }

    public function test_borrar_la_linea_devuelve_el_movimiento(): void
    {
        $articulo = $this->articulo(stock: 10);
        $entrada = $this->entrada();

        $linea = $entrada->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 4,
            'price' => 25,
        ]);

        $this->assertSame(6, $articulo->fresh()->stock);

        $linea->delete();

        $this->assertSame(10, $articulo->fresh()->stock);
    }

    public function test_borrar_la_entrada_devuelve_el_stock(): void
    {
        $articulo = $this->articulo(stock: 10);
        $entrada = $this->entrada();

        $entrada->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 2,
            'price' => 25,
        ]);

        $entrada->delete();

        $this->assertSame(10, $articulo->fresh()->stock);
    }

    public function test_cambiar_la_cantidad_ajusta_la_diferencia(): void
    {
        $articulo = $this->articulo(stock: 10);
        $entrada = $this->entrada();

        $linea = $entrada->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 2,
            'price' => 25,
        ]);

        $linea->update(['quantity' => 5]);

        $this->assertSame(5, $articulo->fresh()->stock);
    }

    public function test_no_deja_vender_mas_de_lo_que_hay(): void
    {
        $articulo = $this->articulo(stock: 3);
        $entrada = $this->entrada();

        $this->expectException(ValidationException::class);

        $entrada->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 4,
            'price' => 25,
        ]);
    }

    public function test_un_articulo_serializado_no_se_puede_usar_en_caja(): void
    {
        $moto = $this->articulo(stock: 0, tracking: Article::TRACKING_SERIAL);
        $entrada = $this->entrada();

        $this->expectException(ValidationException::class);

        $entrada->items()->create([
            'article_id' => $moto->id,
            'quantity' => 1,
            'price' => 1500,
        ]);
    }

    public function test_la_linea_suma_su_total(): void
    {
        $articulo = $this->articulo();
        $entrada = $this->entrada();

        $linea = $entrada->items()->create([
            'article_id' => $articulo->id,
            'quantity' => 3,
            'price' => 25,
        ]);

        $this->assertSame(75.0, $linea->fresh()->total);
    }
}
