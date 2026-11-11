<?php

namespace App\Filament\Exports\Outflow;

use App\Models\Outflow;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class OutflowExporter extends Exporter
{
    protected static ?string $model = Outflow::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('invoice_date')->label(__('ui.columns.invoice_date')),
            ExportColumn::make('company')->label('Empresa'),
            ExportColumn::make('invoice_code')->label(__('ui.columns.invoice_code')),
            ExportColumn::make('quantity')->label(__('ui.columns.quantity')),
            ExportColumn::make('amount')->label(__('ui.columns.amount')),
            ExportColumn::make('description')->label(__('ui.columns.description')),
            ExportColumn::make('source')->label('Origen'),
            ExportColumn::make('area')->label(__('ui.columns.area')),
            ExportColumn::make('id')
                ->label('ID interno'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your outflow export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
