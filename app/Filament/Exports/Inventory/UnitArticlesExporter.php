<?php

namespace App\Filament\Exports\Inventory;

use App\Models\ArticleUnit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class UnitArticlesExporter extends Exporter
{
    protected static ?string $model = ArticleUnit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources.inventory.article_units.id')),

            ExportColumn::make('article.name')
                ->label(__('resources.inventory.article_units.article')),

            ExportColumn::make('vin')
                ->label(__('resources.inventory.article_units.vin')),

            ExportColumn::make('engine_number')
                ->label(__('resources.inventory.article_units.engine_number')),

            ExportColumn::make('plate')
                ->label(__('resources.inventory.article_units.plate')),

            ExportColumn::make('color')
                ->label(__('resources.inventory.article_units.color')),

            ExportColumn::make('cash_price')
                ->label(__('resources.inventory.article_units.cash_price')),

            ExportColumn::make('status')
                ->label(__('resources.inventory.article_units.status'))
                ->formatStateUsing(fn (?string $state): string => filled($state)
                    ? __('ui.unit_status.' . $state)
                    : ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('ui.exports.done', ['count' => Number::format($export->successful_rows)]);
    }
}
