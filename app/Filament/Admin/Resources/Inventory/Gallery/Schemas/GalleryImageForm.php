<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Formulario de una foto de la galería.
 *
 * Sin tarjeta intermedia y con el archivo a todo el ancho: en el modal, una sección
 * anidada dejaba los campos angostos (el select cortaba el texto y el input de archivo
 * no entraba).
 */
class GalleryImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                FileUpload::make('path')
                    ->label(__('ui.images.image'))
                    ->disk('attachments')
                    ->directory('gallery')
                    ->image()
                    ->maxSize(10240)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->columnSpanFull(),

                TextInput::make('sort')
                    ->label(__('ui.images.sort'))
                    ->numeric()
                    ->default(0)
                    ->helperText(__('ui.images.sort_help')),
            ])
            ->columns(2);
    }
}
