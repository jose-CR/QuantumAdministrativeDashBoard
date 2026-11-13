<?php

namespace App\Filament\Exports\Credits;

use App\Models\Installment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Cuotas: a qué crédito pertenecen, cuánto vencen, cuánto se pagó y cuánto falta.
 */
class InstallmentsExporter extends Exporter
{
    protected static ?string $model = Installment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ui.columns.internal_id')),

            ExportColumn::make('credit_id')
                ->label(__('ui.columns.credit')),

            ExportColumn::make('credit.customer.full_name')
                ->label(__('ui.columns.customer')),

            ExportColumn::make('number')
                ->label(__('ui.columns.installment_number')),

            ExportColumn::make('amount')
                ->label(__('ui.columns.amount')),

            ExportColumn::make('paid_amount')
                ->label(__('ui.columns.paid_amount')),

            ExportColumn::make('remaining_balance')
                ->label(__('ui.columns.remaining_balance')),

            ExportColumn::make('due_date')
                ->label(__('ui.columns.due_date')),

            ExportColumn::make('paid_at')
                ->label(__('ui.columns.payment_date')),

            ExportColumn::make('status')
                ->label(__('ui.columns.status'))
                ->formatStateUsing(fn (?string $state): string => filled($state)
                    ? __('ui.installment_status.' . $state)
                    : ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('ui.exports.done', ['count' => Number::format($export->successful_rows)]);
    }
}
