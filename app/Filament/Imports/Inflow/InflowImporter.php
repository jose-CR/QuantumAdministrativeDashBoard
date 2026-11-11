<?php

namespace App\Filament\Imports\Inflow;

use App\Models\Inflow;
use App\Rules\Flow\InflowRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class InflowImporter extends Importer
{
    protected static ?string $model = Inflow::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('invoice_number')
                ->label(__('ui.columns.invoice_number'))
                ->rules(InflowRules::import()['invoice_number']),

            ImportColumn::make('date')
                ->label(__('ui.columns.date'))
                ->rules(InflowRules::import()['date']),

            ImportColumn::make('description')
                ->label(__('ui.columns.description'))
                ->rules(InflowRules::import()['description']),

            ImportColumn::make('customer_id')
                ->label(__('ui.columns.customer'))
                ->rules(InflowRules::import()['customer_id']),

            ImportColumn::make('amount_paid')
                ->rules(InflowRules::import()['amount_paid']),

            ImportColumn::make('amount')
                ->label(__('ui.columns.amount'))
                ->rules(InflowRules::import()['amount']),

            ImportColumn::make('payment_method')
                ->label(__('ui.columns.payment_method'))
                ->rules(InflowRules::import()['payment_method']),

            ImportColumn::make('transfer_number')
                ->label(__('ui.columns.transfer_number'))
                ->rules(InflowRules::import()['transfer_number']),

            ImportColumn::make('bank_id')
                ->label(__('ui.columns.bank'))
                ->rules(InflowRules::import()['bank_id']),

            ImportColumn::make('transfer_date')
                ->label(__('ui.columns.transfer_date'))
                ->rules(InflowRules::import()['transfer_date']),

            ImportColumn::make('payment_status')
                ->label(__('ui.columns.payment_status'))
                ->rules(InflowRules::import()['payment_status']),

            ImportColumn::make('salesperson')
                ->label(__('ui.columns.salesperson'))
                ->rules(InflowRules::import()['salesperson']),

            ImportColumn::make('notes')
                ->label(__('ui.columns.notes'))
                ->rules(InflowRules::import()['notes']),
        ];
    }

    public function resolveRecord(): Inflow
    {
        return Inflow::firstOrNew([
            'invoice_number' => $this->data['invoice_number'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your inflow import has completed and '
            . Number::format($import->successful_rows)
            . ' '
            . str('row')->plural($import->successful_rows)
            . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                . Number::format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to import.';
        }

        return $body;
    }
}