<?php

namespace App\Filament\Exports\Credits;

use App\Models\Credit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class LoansExporter extends Exporter
{
    protected static ?string $model = Credit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources.credits.fields.id')),

            ExportColumn::make('client_id')
                ->label(__('resources.credits.fields.client')),

            ExportColumn::make('article_unit_id')
                ->label(__('resources.credits.fields.article_unit')),

            ExportColumn::make('refinanced_from_id')
                ->label(__('resources.credits.fields.refinanced_from')),

            ExportColumn::make('initial_amount')
                ->label(__('resources.credits.fields.initial_amount')),

            ExportColumn::make('down_payment')
                ->label(__('resources.credits.fields.down_payment')),

            ExportColumn::make('financed_amount')
                ->label(__('resources.credits.fields.financed_amount')),

            ExportColumn::make('installments')
                ->label(__('resources.credits.fields.installments')),

            ExportColumn::make('installment_amount')
                ->label(__('resources.credits.fields.installment_amount')),

            ExportColumn::make('periodicity')
                ->label(__('resources.credits.fields.periodicity')),

            ExportColumn::make('interest_rate')
                ->label(__('resources.credits.fields.interest_rate')),

            ExportColumn::make('total_interest')
                ->label(__('resources.credits.fields.total_interest')),

            ExportColumn::make('total_amount')
                ->label(__('resources.credits.fields.total_amount')),

            ExportColumn::make('pending_balance')
                ->label(__('resources.credits.fields.pending_balance')),

            ExportColumn::make('start_date')
                ->label(__('resources.credits.fields.start_date')),

            ExportColumn::make('payment_day')
                ->label(__('resources.credits.fields.payment_day')),

            ExportColumn::make('payment_month')
                ->label(__('resources.credits.fields.payment_month')),

            ExportColumn::make('status')
                ->label(__('resources.credits.fields.status')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your loans export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
