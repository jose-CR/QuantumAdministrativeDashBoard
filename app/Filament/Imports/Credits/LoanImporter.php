<?php

namespace App\Filament\Imports\Credits;

use App\Models\Credit;
use App\Rules\Credits\LoanRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class LoanImporter extends Importer
{
    protected static ?string $model = Credit::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('client_id')
                ->requiredMapping()
                ->rules(LoanRules::import()['client_id']),

            ImportColumn::make('article_unit_id')
                ->requiredMapping()
                ->rules(LoanRules::import()['article_unit_id']),

            ImportColumn::make('refinanced_from_id')
                ->requiredMapping()
                ->rules(LoanRules::import()['refinanced_from_id']),

            ImportColumn::make('initial_amount')
                ->requiredMapping()
                ->rules(LoanRules::import()['initial_amount']),

            ImportColumn::make('down_payment')
                ->requiredMapping()
                ->rules(LoanRules::import()['down_payment']),

            ImportColumn::make('financed_amount')
                ->requiredMapping()
                ->rules(LoanRules::import()['financed_amount']),

            ImportColumn::make('installments')
                ->requiredMapping()
                ->rules(LoanRules::import()['installments']),

            ImportColumn::make('installment_amount')
                ->requiredMapping()
                ->rules(LoanRules::import()['installment_amount']),

            ImportColumn::make('periodicity')
                ->requiredMapping()
                ->rules(LoanRules::import()['periodicity']),

            ImportColumn::make('interest_rate')
                ->requiredMapping()
                ->rules(LoanRules::import()['interest_rate']),

            ImportColumn::make('total_interest')
                ->requiredMapping()
                ->rules(LoanRules::import()['total_interest']),

            ImportColumn::make('total_amount')
                ->requiredMapping()
                ->rules(LoanRules::import()['total_amount']),

            ImportColumn::make('pending_balance')
                ->requiredMapping()
                ->rules(LoanRules::import()['pending_balance']),

            ImportColumn::make('start_date')
                ->requiredMapping()
                ->rules(LoanRules::import()['start_date']),

            ImportColumn::make('payment_day')
                ->requiredMapping()
                ->rules(LoanRules::import()['payment_day']),

            ImportColumn::make('payment_month')
                ->requiredMapping()
                ->rules(LoanRules::import()['payment_month']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(LoanRules::import()['status']),
        ];
    }

    public function resolveRecord(): Credit
    {
        return Credit::firstOrNew([
            'client_id' => $this->data['client_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your loan import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
