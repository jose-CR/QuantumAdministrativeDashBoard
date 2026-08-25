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
                    ->label(__('resources.inventary.article.category'))
                    ->relationship('category', 'name')
                    ->required(),

                TextInput::make('brand')
                    ->label(__('resources.inventary.article.brand'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('model')
                    ->label(__('resources.inventary.article.model'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('year')
                    ->label(__('resources.inventary.article.year'))
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(date('Y') + 1),

                RichEditor::make('description')
                    ->label(__('resources.inventary.article.description'))
                    ->columnSpanFull(),
            ]);
    }
}
