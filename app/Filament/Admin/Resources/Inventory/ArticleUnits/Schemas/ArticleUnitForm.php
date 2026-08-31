<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Schemas;

use App\Models\ArticleUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->searchable(['brand', 'model']),
                
                Select::make('selected_price')
                        ->label(__('resources.inventary.article_units.cash_price'))
                        ->options(function (Get $get){
                            $articleId = $get('article_id');

                            if (!$articleId){
                                return [];
                            }

                            $prices = ArticleUnit::query()
                                ->where('article_id', $articleId)
                                ->distinct()
                                ->pluck('cash_price', 'cash_price')
                                ->toArray();

                            $prices['new'] = '➕ Nuevo precio';

                            return $prices;
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set){
                            if (!$state){
                                $set('cash_price', null);
                                return;
                            } elseif ($state === 'new'){
                                $set('cash_price', null);
                            }else{
                                $set('cash_price', $state);
                            }
                        }),

                TextInput::make('cash_price')
                    ->label(__('resources.inventary.article_units.cash_price'))
                    ->numeric()
                    ->visible(
                        fn (Get $get) => $get('selected_price') === 'new'
                    ),

                TextInput::make('vin')
                    ->label(__('resources.inventary.article_units.vin')),

                TextInput::make('engine_number')
                    ->label(__('resources.inventary.article_units.engine_number')),

                TextInput::make('plate')
                    ->label(__('resources.inventary.article_units.plate')),

                TextInput::make('color')
                    ->label(__('resources.inventary.article_units.color')),
            
                Select::make('status')
                    ->label(__('resources.inventary.article_units.status'))
                    ->options([
                        'available' => 'Disponible',
                        'reserved'  => 'Reservado',
                        'sold'      => 'Vendido',
                        'rented'    => 'Alquilado',
                        'returned'  => 'Devuelto',
                    ])
                    ->default('available')
                    ->hiddenOn('create'),
            ]);
    }
}
