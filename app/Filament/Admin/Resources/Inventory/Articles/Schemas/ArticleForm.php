<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('brand')
                    ->label('Marca')
                    ->required()
                    ->maxLength(255),

                TextInput::make('model')
                    ->label('Modelo')
                    ->required()
                    ->maxLength(255),

                TextInput::make('year')
                    ->label('Año')
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(date('Y') + 1),

                TextInput::make('cash_price')
                    ->label('Precio Contado')
                    ->numeric()
                    ->prefix('$')
                    ->required(),

                TextInput::make('credit_price')
                    ->label('Precio Crédito')
                    ->numeric()
                    ->prefix('$')
                    ->required(),

                RichEditor::make('descripcion')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }
}
