<?php

namespace App\Filament\Imports\Administration;

use App\Models\Bank;
use App\Rules\Administration\BankRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class BankImporter extends Importer
{
    protected static ?string $model = Bank::class;

    public static function getColumns(): array
    {
        return [
           ImportColumn::make('name')
                ->requiredMapping()
                ->rules(BankRules::import()['name']),
        ];
    }

    public function resolveRecord(): Bank
    {
        return Bank::firstOrNew([
            'name' => $this->data['name'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your bank import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
