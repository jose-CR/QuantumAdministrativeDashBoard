<?php

namespace App\Filament\Imports\Credits;

use App\Models\ClientReference;
use App\Rules\Credits\ReferenceClientRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ReferenceClientImporter extends Importer
{
    protected static ?string $model = ClientReference::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('client_id')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['client_id']),

            ImportColumn::make('reference_type')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['reference_type']),

            ImportColumn::make('full_name')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['full_name']),

            ImportColumn::make('relationship')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['relationship']),

            ImportColumn::make('phone')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['phone']),

            ImportColumn::make('address')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['address']),

            ImportColumn::make('occupation')
                ->requiredMapping()
                ->rules(ReferenceClientRules::import()['occupation']), 
        ];
    }

    public function resolveRecord(): ClientReference
    {
        return ClientReference::firstOrNew([
            'client_id' => $this->data['client_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your reference client import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
