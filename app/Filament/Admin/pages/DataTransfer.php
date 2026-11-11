<?php

namespace App\Filament\Admin\pages;

use App\Filament\Exports\Administration\BanksExporter;
use App\Filament\Exports\Credits\InstallmentsExporter;
use App\Filament\Exports\Credits\LoansExporter;
use App\Filament\Exports\Credits\PaymentHistoriesExporter;
use App\Filament\Exports\CustomerExporter;
use App\Filament\Exports\Inflow\InflowExporter;
use App\Filament\Exports\Inventory\ArticlesExporter;
use App\Filament\Exports\Inventory\CategoriesExporter;
use App\Filament\Exports\Inventory\UnitArticlesExporter;
use App\Filament\Exports\Outflow\OutflowExporter;
use App\Filament\Imports\Administration\BankImporter;
use App\Filament\Imports\Credits\InstallmentImporter;
use App\Filament\Imports\Credits\LoanImporter;
use App\Filament\Imports\Credits\PaymentHistoryImporter;
use App\Filament\Imports\CustomerImporter;
use App\Filament\Imports\Inflow\InflowImporter;
use App\Filament\Imports\Inventory\ArticleImporter;
use App\Filament\Imports\Inventory\ArticleUnitImporter;
use App\Filament\Imports\Inventory\CategoryImporter;
use App\Filament\Imports\Outflow\OutflowImporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ImportAction;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Todos los export e import del panel en una sola pantalla, agrupados por área y,
 * dentro de cada área, por modelo.
 *
 * Los módulos ya no llevan sus botones: en cada listado queda uno que trae acá con
 * el modelo ya elegido (?modelo=inflows).
 */
class DataTransfer extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $slug = 'datos';

    protected static ?int $navigationSort = -9;

    /** Modelo pedido por la ruta; vacío muestra todas las áreas. */
    #[Url]
    public ?string $modelo = null;

    public static function getNavigationLabel(): string
    {
        return __('models.others.data_transfer');
    }

    public function getTitle(): string
    {
        return __('models.others.data_transfer');
    }

    public function getHeading(): string
    {
        return __('models.others.data_transfer');
    }

    public function getSubheading(): ?string
    {
        return __('models.others.data_transfer_hint');
    }

    /**
     * El catálogo: qué área, qué modelo y con qué clases se exporta e importa.
     *
     * @return array<string, array{area: string, label: string, description: string, exporter: class-string, importer: class-string}>
     */
    public static function registry(): array
    {
        return [
            'customers' => [
                'area' => __('models.others.clients'),
                'label' => 'Clientes',
                'description' => 'Ficha completa: documento, contacto, dirección y referencias.',
                'exporter' => CustomerExporter::class,
                'importer' => CustomerImporter::class,
                // El export de clientes se puede filtrar por tipo de documento.
                'filter' => 'document_type',
            ],

            'articles' => [
                'area' => __('models.others.inventary'),
                'label' => 'Artículos',
                'description' => 'Catálogo con categoría, precios y existencias.',
                'exporter' => ArticlesExporter::class,
                'importer' => ArticleImporter::class,
            ],
            'article-units' => [
                'area' => __('models.others.inventary'),
                'label' => 'Unidades con serie',
                'description' => 'VIN o número de serie, estado y precio de cada unidad.',
                'exporter' => UnitArticlesExporter::class,
                'importer' => ArticleUnitImporter::class,
            ],
            'categories' => [
                'area' => __('models.others.inventary'),
                'label' => 'Categorías',
                'description' => 'Árbol de categorías del inventario.',
                'exporter' => CategoriesExporter::class,
                'importer' => CategoryImporter::class,
            ],

            'loans' => [
                'area' => __('models.others.credits'),
                'label' => 'Créditos',
                'description' => 'Crédito completo: cliente, montos, cuotas e interés.',
                'exporter' => LoansExporter::class,
                'importer' => LoanImporter::class,
            ],
            'installments' => [
                'area' => __('models.others.credits'),
                'label' => 'Cuotas',
                'description' => 'Cuotas de todos los créditos, con saldo y estado.',
                'exporter' => InstallmentsExporter::class,
                'importer' => InstallmentImporter::class,
            ],
            'payments' => [
                'area' => __('models.others.credits'),
                'label' => 'Pagos',
                'description' => 'Recibos: crédito, cuota, monto, banco y fecha.',
                'exporter' => PaymentHistoriesExporter::class,
                'importer' => PaymentHistoryImporter::class,
            ],

            'inflows' => [
                'area' => __('models.others.cash'),
                'label' => 'Entradas',
                'description' => 'Ventas: factura, monto, cobrado, por cobrar y artículos.',
                'exporter' => InflowExporter::class,
                'importer' => InflowImporter::class,
            ],
            'outflows' => [
                'area' => __('models.others.cash'),
                'label' => 'Salidas',
                'description' => 'Compras y gastos: proveedor, factura, monto y área.',
                'exporter' => OutflowExporter::class,
                'importer' => OutflowImporter::class,
            ],

            'banks' => [
                'area' => __('models.others.administration'),
                'label' => 'Bancos',
                'description' => 'Catálogo de bancos para los pagos.',
                'exporter' => BanksExporter::class,
                'importer' => BankImporter::class,
            ],
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components($this->body());
    }

    /**
     * @return array<int, mixed>
     */
    protected function body(): array
    {
        $catalogo = static::registry();

        // Ruta específica: /admin/datos?modelo=inflows muestra solo ese modelo.
        if (filled($this->modelo) && isset($catalogo[$this->modelo])) {
            return [
                $this->card($this->modelo, $catalogo[$this->modelo]),
                SchemaActions::make([
                    Action::make('verTodo')
                        ->label(__('models.others.data_transfer_all'))
                        ->link()
                        ->url(static::getUrl()),
                ]),
            ];
        }

        return collect($catalogo)
            ->groupBy('area')
            ->map(fn ($modelos, string $area): Section => Section::make($area)
                ->columns(2)
                ->schema(
                    $modelos
                        ->map(fn (array $modelo, string $clave): Section => $this->card($clave, $modelo))
                        ->values()
                        ->all()
                ))
            ->values()
            ->all();
    }

    /**
     * @param  array{area: string, label: string, description: string, exporter: class-string, importer: class-string}  $modelo
     */
    protected function card(string $clave, array $modelo): Section
    {
        return Section::make($modelo['label'])
            ->key($clave)
            ->description($modelo['description'])
            ->schema([
                SchemaActions::make([
                    $this->exportAction($clave, $modelo),
                    $this->importAction($clave, $modelo),
                ]),
            ]);
    }

    /**
     * @param  array{area: string, label: string, description: string, exporter: class-string, importer: class-string}  $modelo
     */
    protected function exportAction(string $clave, array $modelo): ExportAction
    {
        $accion = ExportAction::make("export-{$clave}")
            ->label(__('models.others.data_transfer_export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->modalHeading(__('models.others.data_transfer_export') . ' · ' . $modelo['label'])
            ->exporter($modelo['exporter'])
            ->formats([ExportFormat::Xlsx])
            ->columnMapping(false)
            ->fileName(fn (Export $export): string => "{$clave}-{$export->getKey()}");

        // El filtro que tenía el botón del módulo sigue valiendo acá.
        if (($modelo['filter'] ?? null) === 'document_type') {
            $accion->modifyQueryUsing(function (Builder $query, array $options) {
                $tipo = $options['document_type'] ?? null;

                return blank($tipo) || $tipo === 'ALL'
                    ? $query
                    : $query->where('document_type', $tipo);
            });
        }

        return $accion;
    }

    /**
     * @param  array{area: string, label: string, description: string, exporter: class-string, importer: class-string}  $modelo
     */
    protected function importAction(string $clave, array $modelo): ImportAction
    {
        return ImportAction::make("import-{$clave}")
            ->label(__('models.others.data_transfer_import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading(__('models.others.data_transfer_import') . ' · ' . $modelo['label'])
            ->importer($modelo['importer']);
    }
}
