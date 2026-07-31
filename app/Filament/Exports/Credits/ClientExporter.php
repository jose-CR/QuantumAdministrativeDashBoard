<?php

namespace App\Filament\Exports\Credits;

use App\Models\Client;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('full_name'),
            ExportColumn::make('identity_document'),
            ExportColumn::make('birth_date'),
            ExportColumn::make('gender'),
            ExportColumn::make('phone_primary'),
            ExportColumn::make('phone_secondary'),
            ExportColumn::make('email'),
            ExportColumn::make('address'),
            ExportColumn::make('occupation'),
            ExportColumn::make('workplace'),
            ExportColumn::make('monthly_income'),
            ExportColumn::make('marital_status'),
            ExportColumn::make('nationality'),
            ExportColumn::make('is_active')
                ->label('Is active'),
            ExportColumn::make('created_at')
                ->label('creation Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your client export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
