<?php

namespace App\Filament\Exports\Credits;

use App\Models\ClientReference;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ClientRelationsExporter extends Exporter
{
    protected static ?string $model = ClientReference::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources.credits.references.id')),

            ExportColumn::make('client_id')
                ->label(__('resources.credits.references.client')),

            ExportColumn::make('reference_type')
                ->label(__('resources.credits.references.reference_type')),

            ExportColumn::make('full_name')
                ->label(__('resources.credits.references.full_name')),

            ExportColumn::make('relationship')
                ->label(__('resources.credits.references.relationship')),

            ExportColumn::make('phone')
                ->label(__('resources.credits.references.phone')),

            ExportColumn::make('address')
                ->label(__('resources.credits.references.address')),

            ExportColumn::make('occupation')
                ->label(__('resources.credits.references.occupation')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your client relations export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
