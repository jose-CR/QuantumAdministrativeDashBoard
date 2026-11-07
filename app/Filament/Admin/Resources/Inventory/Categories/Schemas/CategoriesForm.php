<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label(__('resources.inventary.category.name'))
                    ->required()
                    ->maxLength(255)
                    // Sin esta regla, un nombre repetido llegaba a la base y
                    // reventaba con un error 500 en vez de avisar en el campo.
                    ->unique(ignoreRecord: true),

                // Era 'descripcion': la columna real es `description`, así que lo
                // que se escribía aquí no se guardaba nunca.
                TextInput::make('description')
                    ->label(__('resources.inventary.category.description'))
                    ->maxLength(255),
            ]);
    }
}
