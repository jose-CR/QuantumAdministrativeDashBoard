<?php

namespace Tests\Feature\Inventory;

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
use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Inventario genérico: un artículo puede llevar serie (vehículo) o manejarse
 * por cantidad (casco, repuesto). Las pruebas corren dentro de una transacción
 * y se revierten: no dejan filas en la base.
 */
class InventoryPanelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

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

    /**
     * Borrar el padre arrastra los hijos: la FK es RESTRICT y sin la cascada
     * habría que borrar miles de artículos a mano antes de la categoría.
     */
    public function test_borrar_una_categoria_arrastra_sus_articulos_y_unidades(): void
    {
        [$categoria, $articulo, $unidad] = $this->arbolDeInventario('CASCADA-CAT');

        $this->assertTrue($categoria->delete());

        $this->assertDatabaseMissing('categories', ['id' => $categoria->id]);
        $this->assertDatabaseMissing('articles', ['id' => $articulo->id]);
        $this->assertDatabaseMissing('article_units', ['id' => $unidad->id]);
    }

    public function test_borrar_un_articulo_arrastra_sus_unidades(): void
    {
        [, $articulo, $unidad] = $this->arbolDeInventario('CASCADA-ART');

        $this->assertTrue($articulo->delete());

        $this->assertDatabaseMissing('articles', ['id' => $articulo->id]);
        $this->assertDatabaseMissing('article_units', ['id' => $unidad->id]);
    }

    /**
     * Si la unidad está en un crédito no se borra nada: `credit_items` apunta por
     * relación polimórfica, sin FK, y quedaría huérfano.
     */
    public function test_no_borra_la_categoria_si_una_unidad_esta_en_un_credito(): void
    {
        [$categoria, , $unidad] = $this->arbolDeInventario('CASCADA-CRED');

        $credito = Credit::query()->create([
            'customer_id' => Customer::query()->firstOrFail()->id,
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
            'credit_id' => $credito->id,
            'item_type' => ArticleUnit::class,
            'item_id' => $unidad->id,
            'price' => 100,
        ]);

        $this->assertFalse($categoria->delete());
        $this->assertFalse($categoria->canBeDeletedWithChildren());

        // El aviso tiene que decir qué crédito es, no solo que hay uno.
        $etiqueta = $categoria->creditsBlockingDeletionLabel();
        $this->assertStringContainsString('#' . $credito->id, $etiqueta);
        $this->assertStringContainsString($credito->client->full_name, $etiqueta);

        $this->assertDatabaseHas('categories', ['id' => $categoria->id]);
        $this->assertDatabaseHas('article_units', ['id' => $unidad->id]);
    }

    /**
     * El resumen alimenta la confirmación del panel y la decisión de bloquear.
     * Montar el modal comprueba además que el closure no revienta.
     */
    public function test_la_confirmacion_de_borrado_avisa_la_cascada(): void
    {
        [$categoria] = $this->arbolDeInventario('MODAL-CAT');

        $this->assertSame(
            ['articles' => 1, 'units' => 1, 'units_in_credits' => 0],
            $categoria->deletionSummary(),
        );

        Livewire::test(ListCategories::class)
            ->mountAction(TestAction::make('delete')->table($categoria->getKey()))
            ->assertOk();
    }

    /**
     * @return array{0: Category, 1: Article, 2: ArticleUnit}
     */
    private function arbolDeInventario(string $vin): array
    {
        $categoria = Category::query()->create(['name' => 'Categoría cascada ' . $vin]);

        $articulo = Article::query()->create([
            'category_id' => $categoria->id,
            'name' => 'Artículo cascada ' . $vin,
            'tracking' => Article::TRACKING_SERIAL,
        ]);

        $unidad = ArticleUnit::query()->create([
            'article_id' => $articulo->id,
            'cash_price' => 100,
            'vin' => $vin,
        ]);

        return [$categoria, $articulo, $unidad];
    }
}
