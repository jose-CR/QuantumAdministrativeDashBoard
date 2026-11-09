<?php

namespace Tests\Unit\Inventory;

use App\Models\Article;
use App\Models\ArticleUnit;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de unidades: solo existen para artículos serializados y siempre con VIN.
 */
class ArticleUnitCrudTest extends InventoryTestCase
{
    public function test_crea_una_unidad_con_vin(): void
    {
        $articulo = $this->makeArticle($this->makeCategory());

        $unidad = ArticleUnit::query()->create([
            'article_id' => $articulo->id,
            'cash_price' => 1500,
            'vin' => 'VIN-CREA-001',
            'color' => 'azul',
        ]);

        $this->assertDatabaseHas('article_units', [
            'id' => $unidad->id,
            'vin' => 'VIN-CREA-001',
            'status' => 'available',
        ]);
    }

    public function test_no_crea_una_unidad_sin_vin(): void
    {
        $articulo = $this->makeArticle($this->makeCategory());

        $this->expectException(ValidationException::class);

        ArticleUnit::query()->create([
            'article_id' => $articulo->id,
            'cash_price' => 1500,
        ]);
    }

    public function test_no_crea_una_unidad_para_un_articulo_por_cantidad(): void
    {
        $articulo = $this->makeArticle($this->makeCategory(), [
            'tracking' => Article::TRACKING_NONE,
        ]);

        $this->expectException(ValidationException::class);

        ArticleUnit::query()->create([
            'article_id' => $articulo->id,
            'cash_price' => 35,
            'vin' => 'VIN-NO-VA',
        ]);
    }

    public function test_lee_una_unidad(): void
    {
        $unidad = $this->makeUnit($this->makeArticle($this->makeCategory()), [
            'vin' => 'VIN-LEE-001',
        ]);

        $this->assertSame('VIN-LEE-001', ArticleUnit::query()->find($unidad->id)->vin);
    }

    public function test_actualiza_una_unidad(): void
    {
        $unidad = $this->makeUnit($this->makeArticle($this->makeCategory()));

        $unidad->update([
            'cash_price' => 2000,
            'color' => 'rojo',
            'status' => 'reserved',
        ]);

        $this->assertDatabaseHas('article_units', [
            'id' => $unidad->id,
            'cash_price' => 2000,
            'color' => 'rojo',
            'status' => 'reserved',
        ]);
    }

    public function test_borra_una_unidad(): void
    {
        $unidad = $this->makeUnit($this->makeArticle($this->makeCategory()));

        $this->assertTrue($unidad->delete());
        $this->assertDatabaseMissing('article_units', ['id' => $unidad->id]);
    }

    public function test_el_nombre_para_mostrar_incluye_articulo_y_vin(): void
    {
        $articulo = $this->makeArticle($this->makeCategory(), ['name' => 'Moto eléctrica']);
        $unidad = $this->makeUnit($articulo, ['vin' => 'VIN-NOMBRE-9', 'color' => 'azul']);

        $this->assertStringContainsString('Moto eléctrica', $unidad->display_name);
        $this->assertStringContainsString('VIN-NOMBRE-9', $unidad->display_name);
        $this->assertStringContainsString('azul', $unidad->display_name);
    }
}
