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

            ExportColumn::make('article_id')
                ->label(__('resources.inventory.article_units.article')),

            ExportColumn::make('vin')
                ->label(__('resources.inventory.article_units.vin')),

            ExportColumn::make('engine_number')
                ->label(__('resources.inventory.article_units.engine_number')),

            ExportColumn::make('plate')
                ->label(__('resources.inventory.article_units.plate')),

            ExportColumn::make('color')
                ->label(__('resources.inventory.article_units.color')),

            ExportColumn::make('status')
                ->label(__('resources.inventory.article_units.status')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your unit articles export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
