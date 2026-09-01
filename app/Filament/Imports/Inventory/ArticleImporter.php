<?php

namespace App\Filament\Imports\Inventory;

use App\Models\Article;
use App\Rules\Inventory\ArticleRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ArticleImporter extends Importer
{
    protected static ?string $model = Article::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('category_id')
                ->requiredMapping()
                ->rules(ArticleRules::import()['category_id']),

            ImportColumn::make('brand')
                ->requiredMapping()
                ->rules(ArticleRules::import()['brand']),

            ImportColumn::make('model')
                ->requiredMapping()
                ->rules(ArticleRules::import()['model']),

            ImportColumn::make('year')
                ->requiredMapping()
                ->rules(ArticleRules::import()['year']),

            ImportColumn::make('description')
                ->requiredMapping()
                ->rules(ArticleRules::import()['description']),
        ];
    }

    public function resolveRecord(): Article
    {
        return Article::firstOrNew([
            'category_id' => $this->data['category_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your article import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
