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
            // El id dejó de ser obligatorio: pedirlo en el Excel obligaba a
            // inventar ids y desfasaba la secuencia de Postgres.
            ImportColumn::make('id')
                ->rules($rules['id']),

            ImportColumn::make('article_id')
                ->requiredMapping()
                ->rules($rules['article_id']),

            ImportColumn::make('color')
                ->rules($rules['color']),

            ImportColumn::make('cash_price')
                ->requiredMapping()
                ->rules($rules['cash_price']),

            ImportColumn::make('vin')
                ->requiredMapping()
                ->rules($rules['vin']),

            ImportColumn::make('engine_number')
                ->rules($rules['engine_number']),

            ImportColumn::make('plate')
                ->rules($rules['plate']),

            ImportColumn::make('status')
                ->rules($rules['status']),
        ];
    }

    /**
     * Se actualiza por id si viene, si no por VIN/serie (que es único), y si no
     * existe se crea una unidad nueva.
     */
    public function resolveRecord(): ArticleUnit
    {
        if (filled($this->data['id'] ?? null)) {
            $record = ArticleUnit::find($this->data['id']);

            if ($record) {
                return $record;
            }
        }

        if (filled($this->data['vin'] ?? null)) {
            return ArticleUnit::firstOrNew(['vin' => $this->data['vin']]);
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
