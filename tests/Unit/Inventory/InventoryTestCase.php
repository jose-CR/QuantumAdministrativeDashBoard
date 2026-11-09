<?php

namespace Tests\Unit\Inventory;

use App\Models\Article;
use App\Models\ArticleUnit;
use App\Models\Category;
use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Base de las pruebas de inventario.
 *
 * Viven en tests/Unit porque ejercitan el modelo (CRUD) sin pasar por HTTP.
 * Cada prueba corre dentro de una transacción que se revierte al final: la base
 * de desarrollo queda igual.
 */
abstract class InventoryTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function makeCategory(string $name = 'Categoría de prueba'): Category
    {
        return Category::query()->create(['name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeArticle(Category $category, array $attributes = []): Article
    {
        return Article::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'Artículo de prueba',
            'tracking' => Article::TRACKING_SERIAL,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeUnit(Article $article, array $attributes = []): ArticleUnit
    {
        return ArticleUnit::query()->create(array_merge([
            'article_id' => $article->id,
            'cash_price' => 100,
            'vin' => 'VIN-' . fake()->unique()->numerify('######'),
        ], $attributes));
    }

    /**
     * Crédito con una línea que apunta a la unidad: bloquea el borrado.
     */
    protected function makeCreditFor(ArticleUnit $unit): Credit
    {
        $credit = Credit::query()->create([
            'customer_id' => Customer::factory()->create()->id,
            'initial_amount' => 100,
            'financed_amount' => 100,
            'installments' => 1,
            'installment_amount' => 100,
            'total_amount' => 100,
            'start_date' => today(),
            'payment_day' => 1,
            'status' => 'active',
        ]);

        CreditItem::query()->create([
            'credit_id' => $credit->id,
            'item_type' => ArticleUnit::class,
            'item_id' => $unit->id,
            'price' => 100,
        ]);

        return $credit;
    }
}
