<?php

namespace App\Filament\Exports\Inventory;

use App\Models\Category;
use App\Support\Html;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class CategoriesExporter extends Exporter
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources.inventory.category.id')),
            ExportColumn::make('name')
                ->label(__('resources.inventory.category.name')),
            ExportColumn::make('description')
                ->label(__('resources.inventory.category.description'))
                // Texto plano: el editor guarda HTML y en el Excel viajaba como
                // <p>…</p>, que al reimportar se duplicaba.
                ->formatStateUsing(fn (?string $state): ?string => Html::plain($state)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your categories export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
