<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GalleryImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')
                ->label(__('ui.images.category'))
                ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),

            TextInput::make('name')
                ->label(__('ui.images.name'))
                ->required()
                ->maxLength(255)
                ->helperText(__('ui.images.name_help')),

            FileUpload::make('path')
                ->label(__('ui.images.image'))
                ->disk('attachments')
                ->directory('gallery')
                ->image()
                ->maxSize(10240)
                // Al editar se muestra la que ya tiene; la ruta se guarda en `path`.
                ->required(fn (string $operation): bool => $operation === 'create'),

            TextInput::make('sort')
                ->label(__('ui.images.sort'))
                ->numeric()
                ->default(0)
                ->helperText(__('ui.images.sort_help')),
        ])->columns(2);
    }
}
