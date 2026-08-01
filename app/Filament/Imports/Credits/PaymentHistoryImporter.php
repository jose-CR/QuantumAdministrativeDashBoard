<?php

namespace App\Filament\Imports\Credits;

use App\Models\PaymentHistory;
use App\Rules\Credits\PaymentHistoryRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class PaymentHistoryImporter extends Importer
{
    protected static ?string $model = PaymentHistory::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('credit_id')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['credit_id']),
          
            ImportColumn::make('user_id')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['user_id']),

            ImportColumn::make('bank_id')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['bank_id']),

            ImportColumn::make('amount')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['amount']),

            ImportColumn::make('payment_method')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['payment_method']),

            ImportColumn::make('payment_date')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['payment_date']),

            ImportColumn::make('receipt_number')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['receipt_number']),

            ImportColumn::make('previous_balance')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['previous_balance']),

            ImportColumn::make('new_balance')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['new_balance']),
            
            ImportColumn::make('notes')
                ->requiredMapping()
                ->rules(PaymentHistoryRules::import()['notes']),
        ];
    }

    public function resolveRecord(): PaymentHistory
    {
        return PaymentHistory::firstOrNew([
            'credit_id' => $this->data['credit_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your payment history import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
