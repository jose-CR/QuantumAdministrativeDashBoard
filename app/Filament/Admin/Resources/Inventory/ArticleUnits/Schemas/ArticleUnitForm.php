<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Schemas;

use App\Models\Article;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('article_id')
                    ->label(__('resources.inventary.article.article'))
                    ->relationship('article', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => $record->full_name
                    )
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $article = Article::find($state);

                        $set('cash_price', $article?->cash_price);
                    })
                    ->required(),

                TextInput::make('vin')
                    ->label(__('resources.inventary.article_units.vin'))
                    ->required(),

                TextInput::make('engine_number')
                    ->label(__('resources.inventary.article_units.engine_number'))
                    ->required(),

                TextInput::make('plate')
                    ->label(__('resources.inventary.article_units.plate')),

                TextInput::make('color')
                    ->label(__('resources.inventary.article_units.color'))
                    ->required(),
                
                TextInput::make('cash_price')
                    ->label(__('resources.inventary.article_units.cash_price'))
                    ->disabled()
                    ->dehydrated(false),

                Select::make('status')
                    ->label(__('resources.inventary.article_units.status'))
                    ->options([
                        'available' => 'Disponible',
                        'reserved'  => 'Reservado',
                        'sold'      => 'Vendido',
                    ])
                    ->default('available')
                    ->hiddenOn('create'),
            ]);
    }
}
