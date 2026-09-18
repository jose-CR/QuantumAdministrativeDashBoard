<?php

namespace App\Filament\Exports\Inflow;

use App\Models\Inflow;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InflowExporter extends Exporter
{
    protected static ?string $model = Inflow::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('invoice_number')
                ->label('Número de factura'),

            ExportColumn::make('date')
                ->label('Fecha'),

            ExportColumn::make('description')
                ->label('Descripción'),

            ExportColumn::make('customer')
                ->label('Cliente')
                ->state(fn (Inflow $record) => $record->customer_id),

            ExportColumn::make('amount')
                ->label('Monto'),

            ExportColumn::make('payment_method')
                ->label('Método de pago'),

            ExportColumn::make('transfer_number')
                ->label('Número de transferencia'),

            ExportColumn::make('bank')
                ->label('Banco')
                ->state(fn (Inflow $record) => $record->bank_id),

            ExportColumn::make('transfer_date')
                ->label('Fecha de transferencia'),

            ExportColumn::make('payment_status')
                ->label('Estado de pago'),

            ExportColumn::make('salesperson')
                ->label('Vendedor'),

            ExportColumn::make('notes')
                ->label('Notas'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inflow export has completed and '
            . Number::format($export->successful_rows)
            . ' '
            . str('row')->plural($export->successful_rows)
            . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '
                . Number::format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to export.';
        }

        return $body;
    }
}
