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
                ->state(fn (Inflow $record) => $record->customer_id),

            ExportColumn::make('amount_paid')
                ->label('Cobrado'),

            ExportColumn::make('receivable')
                ->label(__('ui.columns.receivable')),

            ExportColumn::make('amount')
                ->label(__('ui.columns.amount')),

            ExportColumn::make('payment_method')
                ->label(__('ui.columns.payment_method'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'transfer' => 'Transferencia',
                        default => $state,
                    }),

            ExportColumn::make('transfer_number')
                ->label(__('ui.columns.transfer_number')),

            ExportColumn::make('bank')
                ->label(__('ui.columns.bank'))
                ->state(fn (Inflow $record) => $record->bank_id),

            ExportColumn::make('transfer_date')
                ->label(__('ui.columns.transfer_date')),

            ExportColumn::make('payment_status')
                ->label(__('ui.columns.payment_status'))
                ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        'paid' => 'Pagado',
                        default => $state,
                    }),

            ExportColumn::make('salesperson')
                ->label(__('ui.columns.salesperson')),

            ExportColumn::make('notes')
                ->label(__('ui.columns.notes')),
            
            ExportColumn::make('id')
                ->label('ID interno'),
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
