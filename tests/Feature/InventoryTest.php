<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\CreateArticleUnit;
use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\EditArticleUnit;
use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\ListArticleUnits;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\CreateArticle;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\EditArticle;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\ListArticles;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\CreateCategories;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\EditCategories;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\ListCategories;
use App\Models\Article;
use App\Models\ArticleUnit;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Inventario genérico: un artículo puede llevar serie (vehículo) o manejarse
 * por cantidad (casco, repuesto). Las pruebas corren dentro de una transacción
 * y se revierten: no dejan filas en la base.
 */
class InventoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_las_pantallas_de_inventario_responden(): void
    {
        foreach ([
            ListCategories::class,
            CreateCategories::class,
            ListArticles::class,
            CreateArticle::class,
            ListArticleUnits::class,
            CreateArticleUnit::class,
        ] as $page) {
            Livewire::test($page)->assertOk();
        }
    }

    public function test_las_pantallas_de_edicion_responden(): void
    {
        $categoria = Category::query()->firstOrFail();
        $articulo = Article::query()->firstOrFail();
        $unidad = ArticleUnit::query()->firstOrFail();

        Livewire::test(EditCategories::class, ['record' => $categoria->getRouteKey()])->assertOk();
        Livewire::test(EditArticle::class, ['record' => $articulo->getRouteKey()])->assertOk();
        Livewire::test(EditArticleUnit::class, ['record' => $unidad->getRouteKey()])->assertOk();
    }

    public function test_la_descripcion_de_la_categoria_se_guarda(): void
    {
        Livewire::test(CreateCategories::class)
            ->fillForm([
                'name' => 'Categoría de prueba',
                'description' => 'Texto que antes se perdía',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Categoría de prueba',
            'description' => 'Texto que antes se perdía',
        ]);
    }

    public function test_no_permite_repetir_el_nombre_de_una_categoria(): void
    {
        $existente = Category::query()->firstOrFail();

        Livewire::test(CreateCategories::class)
            ->fillForm(['name' => $existente->name])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_crea_un_articulo_por_cantidad_sin_marca_ni_ano(): void
    {
        $categoria = Category::query()->firstOrFail();

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'category_id' => $categoria->id,
                'name' => 'Casco talla M',
                'tracking' => Article::TRACKING_NONE,
                'cash_price' => 35,
                'stock' => 8,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $articulo = Article::query()->where('name', 'Casco talla M')->firstOrFail();

        $this->assertFalse($articulo->tracksSerials());
        $this->assertSame(8, $articulo->stock);
        $this->assertSame(0, $articulo->units()->count());
    }

    public function test_un_articulo_por_cantidad_no_acepta_unidades(): void
    {
        $categoria = Category::query()->firstOrFail();

        $articulo = Article::query()->create([
            'category_id' => $categoria->id,
            'name' => 'Casco sin unidades',
            'tracking' => Article::TRACKING_NONE,
        ]);

        $this->expectException(ValidationException::class);

        ArticleUnit::query()->create([
            'article_id' => $articulo->id,
            'cash_price' => 35,
            'vin' => 'SERIE-INVALIDA',
        ]);
    }

    public function test_una_unidad_serializada_exige_vin(): void
    {
        $articulo = Article::query()->serialTracked()->firstOrFail();

        Livewire::test(CreateArticleUnit::class)
            ->fillForm([
                'article_id' => $articulo->id,
                'cash_price' => 100,
            ])
            ->call('create')
            ->assertHasFormErrors(['vin']);
    }

    public function test_el_nombre_del_articulo_se_compone_solo(): void
    {
        $categoria = Category::query()->firstOrFail();

        $articulo = Article::query()->create([
            'category_id' => $categoria->id,
            'brand' => 'Motomel',
            'model' => 'Blitz',
            'year' => 2024,
            'tracking' => Article::TRACKING_SERIAL,
        ]);

        $this->assertSame('Motomel Blitz 2024', $articulo->name);
    }
}
