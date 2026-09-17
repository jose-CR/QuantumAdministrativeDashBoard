<?php

namespace App\Filament\Imports\Inventory;

use App\Models\ArticleUnit;
use App\Rules\Inventory\ArticleUnitRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ArticleUnitImporter extends Importer
{
    protected static ?string $model = ArticleUnit::class;

    public static function getColumns(): array
    {
        $rules = ArticleUnitRules::import();

        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules($rules['id'] ?? ['integer']),

            ImportColumn::make('article_id')
                ->requiredMapping()
                ->rules($rules['article_id']),

            ImportColumn::make('color')
                ->rules($rules['color']),

            ImportColumn::make('cash_price')
                ->requiredMapping()
                ->rules($rules['cash_price']),

            ImportColumn::make('vin')
                ->rules($rules['vin']),

            ImportColumn::make('engine_number')
                ->rules($rules['engine_number']),

            ImportColumn::make('plate')
                ->rules($rules['plate']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->rules($rules['status']),
        ];
    }

    public function resolveRecord(): ArticleUnit
    {
        if (! empty($this->data['id'])) {
            $record = ArticleUnit::find($this->data['id']);

            if ($record) {
                return $record;
            }
        }

        return new ArticleUnit();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your article unit import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
