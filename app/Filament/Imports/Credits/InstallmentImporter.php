<?php

namespace App\Filament\Imports\Credits;

use App\Models\Installment;
use App\Rules\Credits\InstallmentRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class InstallmentImporter extends Importer
{
    protected static ?string $model = Installment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('credit_id')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['credit_id']),
            
            ImportColumn::make('number')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['number']),

            ImportColumn::make('amount')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['amount']),

            ImportColumn::make('due_date')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['due_date']),

            ImportColumn::make('paid_at')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['paid_at']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['status']),

            ImportColumn::make('remaining_balance')
                ->requiredMapping()
                ->rules(InstallmentRules::import()['remaining_balance']),
        ];
    }

    public function resolveRecord(): Installment
    {
        return Installment::firstOrNew([
            'credit_id' => $this->data['credit_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your installment import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
