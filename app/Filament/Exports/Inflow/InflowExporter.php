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
                ->label(__('ui.columns.invoice_number')),

            ExportColumn::make('date')
                ->label(__('ui.columns.date')),

            ExportColumn::make('description')
                ->label(__('ui.columns.description')),

            ExportColumn::make('customer')
                ->label(__('ui.columns.customer'))
                ->state(fn (Inflow $record): ?string => $record->customer?->full_name),

            ExportColumn::make('amount_paid')
                ->label(__('ui.widgets.collected')),

            ExportColumn::make('receivable')
                ->label(__('ui.columns.receivable')),

            ExportColumn::make('amount')
                ->label(__('ui.columns.amount')),

            ExportColumn::make('payment_method')
                ->label(__('ui.columns.payment_method'))
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? __('ui.payment_methods.' . $state)
                        : ''),

            ExportColumn::make('transfer_number')
                ->label(__('ui.columns.transfer_number')),

            ExportColumn::make('bank')
                ->label(__('ui.columns.bank'))
                ->state(fn (Inflow $record): ?string => $record->bank?->name),

            ExportColumn::make('transfer_date')
                ->label(__('ui.columns.transfer_date')),

            ExportColumn::make('payment_status')
                ->label(__('ui.columns.payment_status'))
                ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? __('ui.payment_status.' . $state)
                        : ''),

            ExportColumn::make('salesperson')
                ->label(__('ui.columns.salesperson')),

            ExportColumn::make('items')
                ->label(__('ui.columns.items_sold'))
                ->state(fn (Inflow $record): string => $record->items
                    ->map(fn ($item): string => ((int) $item->quantity > 1 ? $item->quantity . ' × ' : '')
                        . ($item->article?->name ?? '—'))
                    ->implode(', ')),

            ExportColumn::make('notes')
                ->label(__('ui.columns.notes')),
            
            ExportColumn::make('id')
                ->label(__('ui.columns.internal_id')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('ui.exports.done', ['count' => Number::format($export->successful_rows)]);
    }
}
