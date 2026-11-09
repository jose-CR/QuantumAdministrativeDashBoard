<?php

namespace Tests\Unit\Inventory;

use App\Models\Category;

/**
 * CRUD de categorías contra el modelo.
 */
class CategoryCrudTest extends InventoryTestCase
{
    public function test_crea_una_categoria(): void
    {
        $categoria = Category::query()->create([
            'name' => 'Baterías',
            'description' => 'Baterías de litio',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $categoria->id,
            'name' => 'Baterías',
            'description' => 'Baterías de litio',
        ]);
    }

    public function test_lee_una_categoria(): void
    {
        $categoria = $this->makeCategory('Cascos');

        $encontrada = Category::query()->find($categoria->id);

        $this->assertSame('Cascos', $encontrada->name);
    }

    public function test_actualiza_una_categoria(): void
    {
        $categoria = $this->makeCategory('Nombre viejo');

        $categoria->update(['name' => 'Nombre nuevo', 'description' => 'Algo']);

        $this->assertDatabaseHas('categories', [
            'id' => $categoria->id,
            'name' => 'Nombre nuevo',
            'description' => 'Algo',
        ]);
    }

    public function test_borra_una_categoria_sin_articulos(): void
    {
        $categoria = $this->makeCategory();

        $this->assertTrue($categoria->delete());
        $this->assertDatabaseMissing('categories', ['id' => $categoria->id]);
    }

    public function test_borrar_la_categoria_borra_sus_articulos_y_sus_unidades(): void
    {
        $categoria = $this->makeCategory();
        $articulo = $this->makeArticle($categoria);
        $unidad = $this->makeUnit($articulo);

        $this->assertSame(
            ['articles' => 1, 'units' => 1, 'units_in_credits' => 0],
            $categoria->deletionSummary(),
        );

        $this->assertTrue($categoria->delete());

        $this->assertDatabaseMissing('categories', ['id' => $categoria->id]);
        $this->assertDatabaseMissing('articles', ['id' => $articulo->id]);
        $this->assertDatabaseMissing('article_units', ['id' => $unidad->id]);
    }

    public function test_no_borra_la_categoria_si_una_unidad_esta_en_un_credito(): void
    {
        $categoria = $this->makeCategory();
        $articulo = $this->makeArticle($categoria);
        $unidad = $this->makeUnit($articulo);

        $this->makeCreditFor($unidad);

        $this->assertFalse($categoria->canBeDeletedWithChildren());
        $this->assertFalse($categoria->delete());

        $this->assertDatabaseHas('categories', ['id' => $categoria->id]);
        $this->assertDatabaseHas('articles', ['id' => $articulo->id]);
        $this->assertDatabaseHas('article_units', ['id' => $unidad->id]);
    }

    public function test_una_categoria_sin_articulos_se_puede_borrar_siempre(): void
    {
        $categoria = $this->makeCategory();

        $this->assertTrue($categoria->canBeDeletedWithChildren());
        $this->assertSame(
            ['articles' => 0, 'units' => 0, 'units_in_credits' => 0],
            $categoria->deletionSummary(),
        );
    }
}
