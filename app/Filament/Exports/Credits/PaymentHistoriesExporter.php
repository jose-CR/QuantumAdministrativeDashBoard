<?php

namespace App\Filament\Exports\Credits;

use App\Models\PaymentHistory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PaymentHistoriesExporter extends Exporter
{
    protected static ?string $model = PaymentHistory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('resources.credits.payment_histories.id')),

            ExportColumn::make('credit_id')
                ->label(__('resources.credits.payment_histories.credit')),

            ExportColumn::make('user_id')
                ->label(__('resources.credits.payment_histories.user')),

            ExportColumn::make('bank_id')
                ->label(__('resources.credits.payment_histories.bank')),

            ExportColumn::make('amount')
                ->label(__('resources.credits.payment_histories.amount')),

            ExportColumn::make('payment_method')
                ->label(__('resources.credits.payment_histories.payment_method')),

            ExportColumn::make('payment_date')
                ->label(__('resources.credits.payment_histories.payment_date')),

            ExportColumn::make('receipt_number')
                ->label(__('resources.credits.payment_histories.receipt_number')),

            ExportColumn::make('previous_balance')
                ->label(__('resources.credits.payment_histories.previous_balance')),

            ExportColumn::make('new_balance')
                ->label(__('resources.credits.payment_histories.new_balance')),

            ExportColumn::make('notes')
                ->label(__('resources.credits.payment_histories.notes')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payment histories export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
