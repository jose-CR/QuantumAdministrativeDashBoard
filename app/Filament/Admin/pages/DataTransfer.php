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
 * Every export and import of the panel in one screen, grouped by area and, inside
 * each area, by model.
 *
 * Modules no longer carry their own buttons: each list has one that comes here with
 * the model already selected (?model=inflows).
 */
class DataTransfer extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $slug = 'data';

    protected static ?int $navigationSort = -9;

    /** Model requested by the route; empty shows every area. */
    #[Url]
    public ?string $model = null;

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
     * The catalog: which area, which model and which classes export and import it.
     *
     * @return array<string, array{area: string, label: string, description: string, exporter: class-string, importer: class-string, filter?: string}>
     */
    public static function registry(): array
    {
        return [
            'customers' => [
                'area' => __('models.others.clients'),
                'label' => __('models.data_transfer.customers.label'),
                'description' => __('models.data_transfer.customers.description'),
                'exporter' => CustomerExporter::class,
                'importer' => CustomerImporter::class,
                // The customer export can be filtered by document type.
                'filter' => 'document_type',
            ],

            'articles' => [
                'area' => __('models.others.inventory'),
                'label' => __('models.data_transfer.articles.label'),
                'description' => __('models.data_transfer.articles.description'),
                'exporter' => ArticlesExporter::class,
                'importer' => ArticleImporter::class,
            ],
            'article-units' => [
                'area' => __('models.others.inventory'),
                'label' => __('models.data_transfer.article_units.label'),
                'description' => __('models.data_transfer.article_units.description'),
                'exporter' => UnitArticlesExporter::class,
                'importer' => ArticleUnitImporter::class,
            ],
            'categories' => [
                'area' => __('models.others.inventory'),
                'label' => __('models.data_transfer.categories.label'),
                'description' => __('models.data_transfer.categories.description'),
                'exporter' => CategoriesExporter::class,
                'importer' => CategoryImporter::class,
            ],

            'loans' => [
                'area' => __('models.others.credits'),
                'label' => __('models.data_transfer.loans.label'),
                'description' => __('models.data_transfer.loans.description'),
                'exporter' => LoansExporter::class,
                'importer' => LoanImporter::class,
            ],
            'installments' => [
                'area' => __('models.others.credits'),
                'label' => __('models.data_transfer.installments.label'),
                'description' => __('models.data_transfer.installments.description'),
                'exporter' => InstallmentsExporter::class,
                'importer' => InstallmentImporter::class,
            ],
            'payments' => [
                'area' => __('models.others.credits'),
                'label' => __('models.data_transfer.payments.label'),
                'description' => __('models.data_transfer.payments.description'),
                'exporter' => PaymentHistoriesExporter::class,
                'importer' => PaymentHistoryImporter::class,
            ],

            'inflows' => [
                'area' => __('models.others.cash'),
                'label' => __('models.data_transfer.inflows.label'),
                'description' => __('models.data_transfer.inflows.description'),
                'exporter' => InflowExporter::class,
                'importer' => InflowImporter::class,
            ],
            'outflows' => [
                'area' => __('models.others.cash'),
                'label' => __('models.data_transfer.outflows.label'),
                'description' => __('models.data_transfer.outflows.description'),
                'exporter' => OutflowExporter::class,
                'importer' => OutflowImporter::class,
            ],

            'banks' => [
                'area' => __('models.others.administration'),
                'label' => __('models.data_transfer.banks.label'),
                'description' => __('models.data_transfer.banks.description'),
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
        $registry = static::registry();

        // Specific route: /admin/data?model=inflows shows only that card.
        if (filled($this->model) && isset($registry[$this->model])) {
            return [
                $this->card($this->model, $registry[$this->model]),
                SchemaActions::make([
                    Action::make('seeAll')
                        ->label(__('models.others.data_transfer_all'))
                        ->link()
                        ->url(static::getUrl()),
                ]),
            ];
        }

        return collect($registry)
            ->groupBy('area')
            ->map(fn ($models, string $area): Section => Section::make($area)
                ->columns(2)
                ->schema(
                    $models
                        ->map(fn (array $model, string $key): Section => $this->card($key, $model))
                        ->values()
                        ->all()
                ))
            ->values()
            ->all();
    }

    /**
     * @param  array{area: string, label: string, description: string, exporter: class-string, importer: class-string, filter?: string}  $model
     */
    protected function card(string $key, array $model): Section
    {
        return Section::make($model['label'])
            ->key($key)
            ->description($model['description'])
            ->schema([
                SchemaActions::make([
                    $this->exportAction($key, $model),
                    $this->importAction($key, $model),
                ]),
            ]);
    }

    /**
     * @param  array{area: string, label: string, description: string, exporter: class-string, importer: class-string, filter?: string}  $model
     */
    protected function exportAction(string $key, array $model): ExportAction
    {
        $action = ExportAction::make("export-{$key}")
            ->label(__('models.others.data_transfer_export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->modalHeading(__('models.others.data_transfer_export') . ' · ' . $model['label'])
            ->exporter($model['exporter'])
            ->formats([ExportFormat::Xlsx])
            ->columnMapping(false)
            ->fileName(fn (Export $export): string => "{$key}-{$export->getKey()}");

        // The filter the module button had keeps working here.
        if (($model['filter'] ?? null) === 'document_type') {
            $action->modifyQueryUsing(function (Builder $query, array $options) {
                $type = $options['document_type'] ?? null;

                return blank($type) || $type === 'ALL'
                    ? $query
                    : $query->where('document_type', $type);
            });
        }

        return $action;
    }

    /**
     * @param  array{area: string, label: string, description: string, exporter: class-string, importer: class-string, filter?: string}  $model
     */
    protected function importAction(string $key, array $model): ImportAction
    {
        return ImportAction::make("import-{$key}")
            ->label(__('models.others.data_transfer_import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading(__('models.others.data_transfer_import') . ' · ' . $model['label'])
            ->importer($model['importer']);
    }
}
