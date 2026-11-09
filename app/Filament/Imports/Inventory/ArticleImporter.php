<?php

namespace App\Filament\Imports\Inventory;

use App\Models\Article;
use App\Rules\Inventory\ArticleRules;
use App\Support\Html;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ArticleImporter extends Importer
{
    protected static ?string $model = Article::class;

    public static function getColumns(): array
    {
        $rules = ArticleRules::import();

        return [
            ImportColumn::make('category_id')
                ->requiredMapping()
                ->rules($rules['category_id']),

            ImportColumn::make('name')
                ->rules($rules['name']),

            ImportColumn::make('sku')
                ->rules($rules['sku']),

            // sin / none: la columna del Excel que define cómo se almacena.
            ImportColumn::make('tracking')
                ->rules($rules['tracking']),

            ImportColumn::make('brand')
                ->rules($rules['brand']),

            ImportColumn::make('model')
                ->rules($rules['model']),

            ImportColumn::make('year')
                ->rules($rules['year']),

            ImportColumn::make('cash_price')
                ->rules($rules['cash_price']),

            ImportColumn::make('stock')
                ->rules($rules['stock']),

            ImportColumn::make('description')
                ->rules($rules['description'])
                // Un Excel viejo trae <p>…</p>: se guarda como texto plano y un
                // <p></p> queda en null, no como etiqueta vacía.
                ->castStateUsing(fn (?string $state): ?string => Html::plain($state)),
        ];
    }

    /**
     * El artículo se identifica por SKU; si el Excel no lo trae, por su nombre
     * dentro de la categoría, y como último recurso por marca + modelo + año.
     *
     * Antes el match era solo marca + modelo + año: dos artículos iguales en
     * categorías distintas se pisaban entre sí.
     */
    public function resolveRecord(): Article
    {
        $query = Article::query()
            ->where('category_id', $this->data['category_id'] ?? null);

        if (filled($this->data['sku'] ?? null)) {
            return $query->firstOrNew(['sku' => $this->data['sku']]);
        }

        if (filled($this->data['name'] ?? null)) {
            return $query->firstOrNew(['name' => $this->data['name']]);
        }

        return $query->firstOrNew([
            'brand' => $this->data['brand'] ?? null,
            'model' => $this->data['model'] ?? null,
            'year' => $this->data['year'] ?? null,
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
