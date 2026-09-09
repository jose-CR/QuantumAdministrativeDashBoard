<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use App\Rules\CustomerRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules(CustomerRules::import()['id']),

            ImportColumn::make('document_type')
                ->requiredMapping()
                ->rules(CustomerRules::import()['document_type']),

            ImportColumn::make('document_number')
                ->requiredMapping()
                ->rules(CustomerRules::import()['document_number']),

            ImportColumn::make('full_name')
                ->requiredMapping()
                ->rules(CustomerRules::import()['full_name']),

            ImportColumn::make('email')
                ->rules(CustomerRules::import()['email']),

            ImportColumn::make('phone_primary')
                ->rules(CustomerRules::import()['phone_primary']),

            ImportColumn::make('phone_secondary')
                ->rules(CustomerRules::import()['phone_secondary']),

            ImportColumn::make('nrc')
                ->rules(CustomerRules::import()['nrc']),

            ImportColumn::make('economic_activity')
                ->rules(CustomerRules::import()['economic_activity']),

            ImportColumn::make('department')
                ->requiredMapping()
                ->rules(CustomerRules::import()['department']),

            ImportColumn::make('municipality')
                ->requiredMapping()
                ->rules(CustomerRules::import()['municipality']),

            ImportColumn::make('district')
                ->requiredMapping()
                ->rules(CustomerRules::import()['district']),

            ImportColumn::make('address')
                ->requiredMapping()
                ->rules(CustomerRules::import()['address']),
        ];
    }

    public function resolveRecord(): Customer
    {
        return Customer::firstOrNew([
            'id' => $this->data['id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your customer import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
