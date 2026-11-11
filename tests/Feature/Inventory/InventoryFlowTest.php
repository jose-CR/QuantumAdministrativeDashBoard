<?php

namespace Tests\Feature\Inventory;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\CreateArticleUnit;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\CreateArticle;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\CreateCategories;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\ListCategories;
use App\Filament\Exports\Inventory\ArticlesExporter;
use App\Filament\Imports\Inventory\ArticleImporter;
use App\Models\Article;
use App\Models\ArticleUnit;
use App\Models\Category;
use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Integración del inventario: se crean los tres registros desde el panel, se usan
 * en un crédito, se intenta borrar, se libera y se borra en cascada. Y el viaje
 * de la descripción al Excel y de vuelta.
 */
class InventoryFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    public function test_el_ciclo_completo_del_inventario_desde_el_panel(): void
    {
        // 1. Categoría desde el panel.
        Livewire::test(CreateCategories::class)
            ->fillForm(['name' => 'Flujo categoría', 'description' => 'nota'])
            ->call('create')
            ->assertHasNoFormErrors();

        $categoria = Category::query()->where('name', 'Flujo categoría')->firstOrFail();

        // 2. Artículo serializado dentro de esa categoría.
        Livewire::test(CreateArticle::class)
            ->fillForm([
                'category_id' => $categoria->id,
                'name' => 'Moto flujo',
                'tracking' => Article::TRACKING_SERIAL,
                'brand' => 'Motomel',
                'model' => 'Blitz',
                'year' => 2026,
                'cash_price' => 1500,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $articulo = Article::query()->where('name', 'Moto flujo')->firstOrFail();

        // 3. Unidad con VIN.
        Livewire::test(CreateArticleUnit::class)
            ->fillForm([
                'article_id' => $articulo->id,
                'cash_price' => 1500,
                'vin' => 'FLUJO-VIN-001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $unidad = ArticleUnit::query()->where('vin', 'FLUJO-VIN-001')->firstOrFail();

        // 4. La unidad entra en un crédito: borrar la categoría se frena y avisa.
        $credito = Credit::query()->create([
            'customer_id' => \App\Models\Customer::factory()->create()->id,
            'initial_amount' => 1500,
            'financed_amount' => 1500,
            'installments' => 1,
            'installment_amount' => 1500,
            'total_amount' => 1500,
            'start_date' => today(),
            'payment_day' => 1,
            'status' => 'active',
        ]);

        CreditItem::query()->create([
            'credit_id' => $credito->id,
            'item_type' => ArticleUnit::class,
            'item_id' => $unidad->id,
            'price' => 1500,
        ]);

        Livewire::test(ListCategories::class)
            ->callAction(TestAction::make('delete')->table($categoria->getKey()))
            ->assertNotified(__('notifications.inventory.category.blocked.title'));

        $this->assertDatabaseHas('categories', ['id' => $categoria->id]);
        $this->assertDatabaseHas('article_units', ['id' => $unidad->id]);

        // 5. Se libera el crédito y la cascada borra categoría, artículo y unidad.
        CreditItem::query()->where('credit_id', $credito->id)->delete();

        Livewire::test(ListCategories::class)
            ->callAction(TestAction::make('delete')->table($categoria->getKey()));

        $this->assertDatabaseMissing('categories', ['id' => $categoria->id]);
        $this->assertDatabaseMissing('articles', ['id' => $articulo->id]);
        $this->assertDatabaseMissing('article_units', ['id' => $unidad->id]);
    }

    public function test_la_descripcion_viaja_al_excel_y_vuelve_sin_html(): void
    {
        $categoria = Category::query()->create(['name' => 'Descripción flujo']);

        $articulo = Article::query()->create([
            'category_id' => $categoria->id,
            'name' => 'Batería 48V',
            'tracking' => Article::TRACKING_NONE,
            'description' => '<p>Nota <b>importante</b></p>',
        ]);

        // Lo que se escribe en la celda del Excel.
        $export = Export::query()->create([
            'file_disk' => 'local',
            'exporter' => ArticlesExporter::class,
            'total_rows' => 1,
            'user_id' => auth()->id(),
        ]);

        $descripcionExportada = $export
            ->getExporter(['description' => 'Descripción'], [])($articulo)[0];

        $this->assertSame('Nota importante', $descripcionExportada);

        // Lo que se guarda al importar esa misma celda (o una hoja vieja).
        $importador = new ArticleImporter(new Import(), ['description' => 'Descripción'], []);

        $columna = collect($importador->getCachedColumns())
            ->first(fn ($column) => $column->getName() === 'description');

        $this->assertSame($descripcionExportada, $columna->castState('<p>Nota <b>importante</b></p>'));
        $this->assertNull($columna->castState('<p></p>'));
    }

    public function test_un_articulo_por_cantidad_no_genera_unidades(): void
    {
        $categoria = Category::query()->create(['name' => 'Accesorios flujo']);

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'category_id' => $categoria->id,
                'name' => 'Casco flujo',
                'tracking' => Article::TRACKING_NONE,
                'cash_price' => 35,
                'stock' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $casco = Article::query()->where('name', 'Casco flujo')->firstOrFail();

        $this->assertSame(10, $casco->stock);
        $this->assertSame(0, $casco->units()->count());

        // El panel ni siquiera lista el artículo para crearle una unidad.
        Livewire::test(CreateArticleUnit::class)
            ->fillForm([
                'article_id' => $casco->id,
                'cash_price' => 35,
                'vin' => 'NO-DEBE-ENTRAR',
            ])
            ->call('create')
            ->assertHasFormErrors();
    }
}
