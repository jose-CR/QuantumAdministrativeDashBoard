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
            ExportColumn::make('invoice_date')->label('Fecha de factura'),
            ExportColumn::make('company')->label('Empresa'),
            ExportColumn::make('invoice_code')->label('Código de factura'),
            ExportColumn::make('quantity')->label('Cantidad'),
            ExportColumn::make('amount')->label('Monto'),
            ExportColumn::make('description')->label('Descripción'),
            ExportColumn::make('source')->label('Origen'),
            ExportColumn::make('area')->label('Área'),
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
