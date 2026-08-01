<?php

namespace App\Filament\Exports\Inventory;

use App\Models\Article;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ArticlesExporter extends Exporter
{
    protected static ?string $model = Article::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources.inventary.article.id')),

            ExportColumn::make('category_id')
                ->label(__('resources.inventary.article.category')),

            ExportColumn::make('brand')
                ->label(__('resources.inventary.article.brand')),

            ExportColumn::make('model')
                ->label(__('resources.inventary.article.model')),

            ExportColumn::make('year')
                ->label(__('resources.inventary.article.year')),

            ExportColumn::make('color')
                ->label(__('resources.inventary.article.color')),

            ExportColumn::make('cash_price')
                ->label(__('resources.inventary.article.cash_price')),

            ExportColumn::make('description')
                ->label(__('resources.inventary.article.description')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your articles export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
