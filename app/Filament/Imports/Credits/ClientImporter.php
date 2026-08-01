<?php

namespace App\Filament\Imports\Credits;

use App\Models\Client;
use App\Rules\Credits\ClientRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ClientImporter extends Importer
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('full_name')
                ->requiredMapping()
                ->rules(ClientRules::import()['full_name']),

            ImportColumn::make('identity_document')
                ->rules(ClientRules::import()['identity_document']),

            ImportColumn::make('birth_date')
                ->rules(ClientRules::import()['birth_date']),

            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(ClientRules::import()['gender']),

            ImportColumn::make('phone_primary')
                ->requiredMapping()
                ->rules(ClientRules::import()['phone_primary']),

            ImportColumn::make('phone_secondary')
                ->rules(ClientRules::import()['phone_secondary']),

            ImportColumn::make('email')
                ->rules(ClientRules::import()['email']),

            ImportColumn::make('address')
                ->requiredMapping()
                ->rules(ClientRules::import()['address']),

            ImportColumn::make('occupation')
                ->rules(ClientRules::import()['occupation']),

            ImportColumn::make('workplace')
                ->rules(ClientRules::import()['workplace']),

            ImportColumn::make('monthly_income')
                ->rules(ClientRules::import()['monthly_income']),

            ImportColumn::make('marital_status')
                ->rules(ClientRules::import()['marital_status']),

            ImportColumn::make('is_active')
                ->rules(ClientRules::import()['is_active']),
        ];
    }

    public function resolveRecord(): Client
    {
        return Client::firstOrNew([
            'email' => $this->data['email'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your client import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
