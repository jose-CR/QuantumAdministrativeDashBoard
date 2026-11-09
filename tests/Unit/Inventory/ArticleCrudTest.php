<?php

namespace Tests\Unit\Inventory;

use App\Models\Article;

/**
 * CRUD de artículos, con los dos criterios de almacenamiento.
 */
class ArticleCrudTest extends InventoryTestCase
{
    public function test_crea_un_articulo_serializado(): void
    {
        $categoria = $this->makeCategory();

        $articulo = Article::query()->create([
            'category_id' => $categoria->id,
            'name' => 'Moto eléctrica',
            'tracking' => Article::TRACKING_SERIAL,
            'brand' => 'Motomel',
            'model' => 'Blitz',
            'year' => 2026,
            'cash_price' => 1500,
        ]);

        $this->assertTrue($articulo->tracksSerials());
        $this->assertFalse($articulo->usesStock());
        $this->assertDatabaseHas('articles', [
            'id' => $articulo->id,
            'tracking' => Article::TRACKING_SERIAL,
        ]);
    }

    public function test_crea_un_articulo_por_cantidad_sin_marca_ni_ano(): void
    {
        $categoria = $this->makeCategory();

        $articulo = Article::query()->create([
            'category_id' => $categoria->id,
            'name' => 'Casco talla M',
            'tracking' => Article::TRACKING_NONE,
            'cash_price' => 35,
            'stock' => 8,
        ]);

        $this->assertFalse($articulo->tracksSerials());
        $this->assertTrue($articulo->usesStock());
        $this->assertSame(8, $articulo->stock);
        $this->assertNull($articulo->brand);
    }

    public function test_el_nombre_se_compone_con_marca_modelo_y_ano(): void
    {
        $articulo = $this->makeArticle($this->makeCategory(), [
            'name' => null,
            'brand' => 'Motomel',
            'model' => 'Blitz',
            'year' => 2024,
        ]);

        $this->assertSame('Motomel Blitz 2024', $articulo->name);
        $this->assertSame('Motomel Blitz 2024', $articulo->full_name);
    }

    public function test_lee_un_articulo(): void
    {
        $articulo = $this->makeArticle($this->makeCategory(), ['name' => 'Batería 48V']);

        $this->assertSame('Batería 48V', Article::query()->find($articulo->id)->name);
    }

    public function test_actualiza_un_articulo(): void
    {
        $articulo = $this->makeArticle($this->makeCategory());

        $articulo->update([
            'cash_price' => 250,
            'stock' => 3,
            'tracking' => Article::TRACKING_NONE,
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $articulo->id,
            'cash_price' => 250,
            'tracking' => Article::TRACKING_NONE,
        ]);
    }

    public function test_borrar_el_articulo_borra_sus_unidades(): void
    {
        $articulo = $this->makeArticle($this->makeCategory());
        $unidad = $this->makeUnit($articulo);

        $this->assertSame(['units' => 1, 'units_in_credits' => 0], $articulo->deletionSummary());
        $this->assertTrue($articulo->delete());

        $this->assertDatabaseMissing('articles', ['id' => $articulo->id]);
        $this->assertDatabaseMissing('article_units', ['id' => $unidad->id]);
    }

    public function test_no_borra_el_articulo_si_una_unidad_esta_en_un_credito(): void
    {
        $articulo = $this->makeArticle($this->makeCategory());
        $unidad = $this->makeUnit($articulo);

        $this->makeCreditFor($unidad);

        $this->assertFalse($articulo->delete());
        $this->assertDatabaseHas('articles', ['id' => $articulo->id]);
        $this->assertDatabaseHas('article_units', ['id' => $unidad->id]);
    }
}
