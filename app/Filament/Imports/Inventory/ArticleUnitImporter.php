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
        return [
            ImportColumn::make('article_id')
                ->requiredMapping()
                ->rules(ArticleUnitRules::import()['article_id']),

            ImportColumn::make('vin')
                ->requiredMapping()
                ->rules(ArticleUnitRules::import()['vin']),

            ImportColumn::make('engine_number')
                ->requiredMapping()
                ->rules(ArticleUnitRules::import()['engine_number']),

            ImportColumn::make('plate')
                ->requiredMapping()
                ->rules(ArticleUnitRules::import()['plate']),

            ImportColumn::make('color')
                ->requiredMapping()
                ->rules(ArticleUnitRules::import()['color']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(ArticleUnitRules::import()['status']),
        ];
    }

    public function resolveRecord(): ArticleUnit
    {
        return ArticleUnit::firstOrNew([
            'article_id' => $this->data['article_id'],
        ]);
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
