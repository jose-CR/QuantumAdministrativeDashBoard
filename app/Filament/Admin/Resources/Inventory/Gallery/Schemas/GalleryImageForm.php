<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('ui.images.title'))
                ->schema([
                    Select::make('category_id')
                        ->label(__('ui.images.category'))
                        ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),

                    TextInput::make('original_name')
                        ->label(__('ui.images.name'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(__('ui.images.name_help')),

                    // El archivo se guarda en el disco privado con su vista previa:
                    // el disco tiene `serve`, así que Filament arma la URL firmada y
                    // al editar se ve la imagen que ya estaba cargada.
                    FileUpload::make('path')
                        ->label(__('ui.images.image'))
                        ->disk('attachments')
                        ->directory('gallery')
                        ->image()
                        ->maxSize(10240)
                        ->required(fn (string $operation): bool => $operation === 'create'),

                    TextInput::make('sort')
                        ->label(__('ui.images.sort'))
                        ->numeric()
                        ->default(0)
                        ->helperText(__('ui.images.sort_help')),
                ])
                ->columns(2),
        ]);
    }
}
