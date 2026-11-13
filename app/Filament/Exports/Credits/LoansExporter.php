<?php

namespace App\Filament\Exports\Credits;

use App\Models\Credit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Créditos: lo que se prestó, cómo se paga y qué se está financiando.
 *
 * Antes pedía `client_id` y `article_unit_id`, que no existen en `credits`: el
 * cliente y el bien salían en blanco.
 */
class LoansExporter extends Exporter
{
    protected static ?string $model = Credit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('ui.columns.internal_id')),

            ExportColumn::make('customer')
                ->label(__('ui.columns.customer'))
                ->state(fn (Credit $record): ?string => $record->customer?->full_name),

            // Qué se está financiando: los bienes del crédito con su cantidad.
            ExportColumn::make('items')
                ->label(__('ui.columns.items'))
                ->state(fn (Credit $record): string => $record->items
                    ->map(fn ($item): string => (string) $item->display_name)
                    ->implode(', ')),

            ExportColumn::make('initial_amount')
                ->label(__('ui.columns.initial_amount')),

            ExportColumn::make('down_payment')
                ->label(__('ui.columns.down_payment')),

            ExportColumn::make('financed_amount')
                ->label(__('ui.columns.financed_amount')),

            ExportColumn::make('installments')
                ->label(__('ui.columns.installments')),

            ExportColumn::make('installment_amount')
                ->label(__('ui.columns.installment_amount')),

            ExportColumn::make('periodicity')
                ->label(__('ui.columns.periodicity'))
                ->formatStateUsing(fn (?string $state): string => filled($state)
                    ? __('ui.periodicity.' . $state)
                    : ''),

            ExportColumn::make('interest_type')
                ->label(__('ui.columns.interest_type'))
                ->formatStateUsing(fn (?string $state): string => $state === 'fixed'
                    ? __('ui.credits.interest_fixed')
                    : __('ui.credits.interest_auto')),

            ExportColumn::make('interest_rate')
                ->label(__('ui.columns.interest_rate')),

            ExportColumn::make('total_interest')
                ->label(__('ui.columns.total_interest')),

            ExportColumn::make('total_amount')
                ->label(__('ui.columns.total_amount')),

            ExportColumn::make('pending_balance')
                ->label(__('ui.columns.pending_balance')),

            ExportColumn::make('start_date')
                ->label(__('ui.columns.start_date')),

            ExportColumn::make('payment_day')
                ->label(__('ui.columns.payment_day')),

            ExportColumn::make('payment_month')
                ->label(__('ui.columns.payment_month')),

            ExportColumn::make('status')
                ->label(__('ui.columns.status'))
                ->formatStateUsing(fn (?string $state): string => filled($state)
                    ? __('ui.credit_status.' . $state)
                    : ''),

            ExportColumn::make('refinanced_from_id')
                ->label(__('ui.columns.refinanced_from')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('ui.exports.done', ['count' => Number::format($export->successful_rows)]);
    }
}
