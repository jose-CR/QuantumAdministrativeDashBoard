<?php

namespace Tests\Unit\Credits;

use App\Models\Article;
use App\Models\Category;
use App\Models\CreditItem;

/**
 * Un crédito puede llevarse artículos que no tienen serie (casco, batería suelta):
 * el ítem guarda la cantidad y el stock del artículo se mueve con él.
 */
class CreditItemStockTest extends CreditTestCase
{
    private function makeArticle(string $tracking = Article::TRACKING_NONE, int $stock = 10): Article
    {
        return Article::query()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Casco abatible',
            'tracking' => $tracking,
            'cash_price' => 25,
            'stock' => $stock,
        ]);
    }

    private function addItem(array $attributes): CreditItem
    {
        $credito = $this->makeCredit([['amount' => 75]]);

        return $credito->items()->create($attributes);
    }

    public function test_un_articulo_por_cantidad_descuenta_el_stock(): void
    {
        $articulo = $this->makeArticle(stock: 10);

        $this->addItem([
            'item_type' => Article::class,
            'item_id' => $articulo->id,
            'price' => 25,
            'quantity' => 3,
        ]);

        $this->assertSame(7, $articulo->fresh()->stock);
    }

    public function test_borrar_el_item_devuelve_el_stock(): void
    {
        $articulo = $this->makeArticle(stock: 10);

        $item = $this->addItem([
            'item_type' => Article::class,
            'item_id' => $articulo->id,
            'price' => 25,
            'quantity' => 4,
        ]);

        $this->assertSame(6, $articulo->fresh()->stock);

        $item->delete();

        $this->assertSame(10, $articulo->fresh()->stock);
    }

    public function test_cambiar_la_cantidad_ajusta_la_diferencia(): void
    {
        $articulo = $this->makeArticle(stock: 10);

        $item = $this->addItem([
            'item_type' => Article::class,
            'item_id' => $articulo->id,
            'price' => 25,
            'quantity' => 3,
        ]);

        $item->update(['quantity' => 5]);

        $this->assertSame(5, $articulo->fresh()->stock);
    }

    public function test_cambiar_de_articulo_devuelve_el_anterior_y_descuenta_el_nuevo(): void
    {
        $casco = $this->makeArticle(stock: 10);
        $guantes = Article::query()->create([
            'category_id' => $casco->category_id,
            'name' => 'Guantes',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 10,
            'stock' => 8,
        ]);

        $item = $this->addItem([
            'item_type' => Article::class,
            'item_id' => $casco->id,
            'price' => 25,
            'quantity' => 2,
        ]);

        $item->update([
            'item_id' => $guantes->id,
            'price' => 10,
            'quantity' => 1,
        ]);

        $this->assertSame(10, $casco->fresh()->stock);
        $this->assertSame(7, $guantes->fresh()->stock);
    }

    public function test_un_articulo_serializado_no_mueve_stock(): void
    {
        // La pieza sale del inventario al asignarse a la unidad; el stock del
        // artículo no se toca.
        $moto = $this->makeArticle(tracking: Article::TRACKING_SERIAL, stock: 0);

        $this->addItem([
            'item_type' => Article::class,
            'item_id' => $moto->id,
            'price' => 1500,
            'quantity' => 1,
        ]);

        $this->assertSame(0, $moto->fresh()->stock);
    }

    public function test_el_nombre_del_item_dice_cuantos_se_llevaron(): void
    {
        $articulo = $this->makeArticle();

        $item = $this->addItem([
            'item_type' => Article::class,
            'item_id' => $articulo->id,
            'price' => 25,
            'quantity' => 3,
        ]);

        $this->assertSame('3 × Casco abatible', $item->fresh()->display_name);
        $this->assertSame(75.0, $item->fresh()->total);
    }

    public function test_borrar_el_credito_devuelve_el_stock(): void
    {
        // Los ítems se van por FK en cascada y esa cascada no dispara eventos:
        // el crédito tiene que borrarlos él para que el stock vuelva.
        $articulo = $this->makeArticle(stock: 10);

        $credito = $this->makeCredit([['amount' => 50]]);
        $credito->items()->create([
            'item_type' => Article::class,
            'item_id' => $articulo->id,
            'price' => 25,
            'quantity' => 2,
        ]);

        $this->assertSame(8, $articulo->fresh()->stock);

        $credito->delete();

        $this->assertSame(10, $articulo->fresh()->stock);
        $this->assertFalse(
            CreditItem::query()->where('credit_id', $credito->id)->exists()
        );
    }
}
