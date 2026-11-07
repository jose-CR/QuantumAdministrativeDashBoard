<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Schemas;

use App\Models\Article;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label(__('resources.inventary.article.category'))
                    ->relationship('category', 'name')
                    ->searchable(['name'])
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label(__('resources.inventary.article.name'))
                    ->maxLength(255)
                    ->placeholder(__('resources.inventary.article.name_placeholder'))
                    ->helperText(__('resources.inventary.article.name_help'))
                    // Dos artículos con el mismo nombre en la misma categoría son
                    // el mismo producto: se avisa aquí y no con un error de base.
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                            ->where('category_id', $get('category_id')),
                    ),

                TextInput::make('sku')
                    ->label(__('resources.inventary.article.sku'))
                    ->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->helperText(__('resources.inventary.article.sku_help')),

                /*
                 * El criterio de almacenamiento lo elige el usuario:
                 *  - serial: vehículos y equipos con VIN o número de serie propio.
                 *  - none:   cascos, repuestos, baterías sueltas… se cuentan por stock.
                 */
                Select::make('tracking')
                    ->label(__('resources.inventary.article.tracking'))
                    ->options([
                        Article::TRACKING_SERIAL => __('resources.inventary.article.tracking_serial'),
                        Article::TRACKING_NONE => __('resources.inventary.article.tracking_none'),
                    ])
                    ->default(Article::TRACKING_SERIAL)
                    ->required()
                    ->live()
                    ->disabled(fn (?Article $record): bool => (bool) $record?->units()->exists())
                    ->helperText(__('resources.inventary.article.tracking_help'))
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        if ($state !== Article::TRACKING_SERIAL) {
                            $set('year', null);
                        }
                    }),

                TextInput::make('brand')
                    ->label(__('resources.inventary.article.brand'))
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => $get('tracking') === Article::TRACKING_SERIAL),

                TextInput::make('model')
                    ->label(__('resources.inventary.article.model'))
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => $get('tracking') === Article::TRACKING_SERIAL),

                TextInput::make('year')
                    ->label(__('resources.inventary.article.year'))
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(date('Y') + 1)
                    // Un casco no tiene año; solo los vehículos lo usan.
                    ->visible(fn (Get $get): bool => $get('tracking') === Article::TRACKING_SERIAL),

                TextInput::make('cash_price')
                    ->label(__('resources.inventary.article.cash_price'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->helperText(__('resources.inventary.article.cash_price_help')),

                TextInput::make('stock')
                    ->label(__('resources.inventary.article.stock'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->visible(fn (Get $get): bool => $get('tracking') === Article::TRACKING_NONE)
                    ->helperText(__('resources.inventary.article.stock_help')),

                RichEditor::make('description')
                    ->label(__('resources.inventary.article.description'))
                    ->columnSpanFull(),
            ]);
    }
}
