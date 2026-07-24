<?php

namespace App\Filament\Imports;

use App\Models\Client;
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
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('identity_document')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('birth_date')
                ->rules(['nullable', 'date']),

            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(['required', 'in:male,female,other']),

            ImportColumn::make('phone_primary')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('phone_secondary')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('address')
                ->requiredMapping()
                ->rules(['required', 'string']),

            ImportColumn::make('occupation')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('workplace')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('monthly_income')
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('marital_status')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('is_active')
                ->rules(['boolean']),
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
