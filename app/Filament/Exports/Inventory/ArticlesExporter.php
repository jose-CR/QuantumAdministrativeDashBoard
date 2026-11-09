<?php

namespace App\Filament\Exports\Inventory;

use App\Models\Article;
use App\Support\Html;
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

            ExportColumn::make('name')
                ->label(__('resources.inventary.article.name')),

            ExportColumn::make('sku')
                ->label(__('resources.inventary.article.sku')),

            ExportColumn::make('category.name')
                ->label(__('resources.inventary.article.category')),

            ExportColumn::make('brand')
                ->label(__('resources.inventary.article.brand')),

            ExportColumn::make('model')
                ->label(__('resources.inventary.article.model')),

            ExportColumn::make('year')
                ->label(__('resources.inventary.article.year')),

            ExportColumn::make('tracking')
                ->label(__('resources.inventary.article.tracking')),

            ExportColumn::make('cash_price')
                ->label(__('resources.inventary.article.cash_price')),

            ExportColumn::make('stock')
                ->label(__('resources.inventary.article.stock')),

            ExportColumn::make('description')
                ->label(__('resources.inventary.article.description'))
                // Texto plano: el editor guarda HTML y en el Excel viajaba como
                // <p>…</p>, que al reimportar se duplicaba.
                ->formatStateUsing(fn (?string $state): ?string => Html::plain($state)),
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
