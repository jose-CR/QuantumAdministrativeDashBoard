<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Schemas;

use App\Models\Article;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ArticleUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                /*
                 * Solo se listan artículos serializados: los que se manejan por
                 * cantidad (casco, repuesto) no tienen unidades, tienen stock.
                 */
                Select::make('article_id')
                    ->label(__('resources.inventary.article.article'))
                    ->relationship(
                        'article',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query->serialTracked(),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Article $record): string => $record->full_name
                    )
                    ->searchable(['name', 'brand', 'model'])
                    ->optionsLimit(50)
                    ->required()
                    ->live()
                    ->helperText(__('resources.inventary.article_units.article_help'))
                    // Al elegir el artículo se precarga su precio de lista; se puede
                    // cambiar por unidad.
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        if (blank($state)) {
                            return;
                        }

                        $set('cash_price', Article::find($state)?->cash_price);
                    }),

                TextInput::make('cash_price')
                    ->label(__('resources.inventary.article_units.cash_price'))
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    // La columna es NOT NULL: sin esta regla, guardar sin precio
                    // terminaba en error de base de datos (500) y no en el campo.
                    ->required(),

                TextInput::make('vin')
                    ->label(__('resources.inventary.article_units.vin'))
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->helperText(__('resources.inventary.article_units.vin_help')),

                TextInput::make('engine_number')
                    ->label(__('resources.inventary.article_units.engine_number'))
                    ->maxLength(255),

                TextInput::make('plate')
                    ->label(__('resources.inventary.article_units.plate'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('color')
                    ->label(__('resources.inventary.article_units.color'))
                    ->maxLength(255),

                Select::make('status')
                    ->label(__('resources.inventary.article_units.status'))
                    ->options([
                        'available' => 'Disponible',
                        'reserved' => 'Reservado',
                        'sold' => 'Vendido',
                        'rented' => 'Alquilado',
                        'returned' => 'Devuelto',
                    ])
                    ->default('available')
                    ->hiddenOn('create'),
            ]);
    }
}
