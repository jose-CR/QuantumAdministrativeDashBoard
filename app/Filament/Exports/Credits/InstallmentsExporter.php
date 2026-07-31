<?php

namespace App\Filament\Exports\Credits;

use App\Models\Installment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InstallmentsExporter extends Exporter
{
    protected static ?string $model = Installment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('credit_id')
                ->label('Credit Id'),
            ExportColumn::make('number'),
            ExportColumn::make('amount'),
            ExportColumn::make('due_date')
                ->label('Due Date'),
            ExportColumn::make('paid_at')
                ->label('Paid At'),
            ExportColumn::make('status'),
            ExportColumn::make('remaining_balance')
                ->label('Remaining Balance'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your installments export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
