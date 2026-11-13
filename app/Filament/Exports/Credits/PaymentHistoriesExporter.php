<?php

namespace App\Filament\Exports\Credits;

use App\Models\PaymentHistory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Pagos: recibo, crédito, cuota, monto, quién lo cargó, banco y fecha.
 */
class PaymentHistoriesExporter extends Exporter
{
    protected static ?string $model = PaymentHistory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ui.columns.internal_id')),

            ExportColumn::make('receipt_number')
                ->label(__('ui.columns.receipt_number')),

            ExportColumn::make('credit_id')
                ->label(__('ui.columns.credit')),

            ExportColumn::make('credit.customer.full_name')
                ->label(__('ui.columns.customer')),

            ExportColumn::make('amount')
                ->label(__('ui.columns.amount')),

            ExportColumn::make('payment_method')
                ->label(__('ui.columns.payment_method'))
                ->formatStateUsing(fn (?string $state): string => filled($state)
                    ? __('ui.payment_methods.' . $state)
                    : ''),

            ExportColumn::make('bank.name')
                ->label(__('ui.columns.bank')),

            ExportColumn::make('user.name')
                ->label(__('ui.columns.user')),

            ExportColumn::make('payment_date')
                ->label(__('ui.columns.payment_date')),

            ExportColumn::make('previous_balance')
                ->label(__('ui.columns.previous_balance')),

            ExportColumn::make('new_balance')
                ->label(__('ui.columns.new_balance')),

            ExportColumn::make('notes')
                ->label(__('ui.columns.notes')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('ui.exports.done', ['count' => Number::format($export->successful_rows)]);
    }
}
