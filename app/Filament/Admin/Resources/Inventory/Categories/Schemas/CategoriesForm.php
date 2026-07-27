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
                    ->required(),

                TextInput::make('descripcion')
                    ->label(__('resources.inventary.category.description')),
                //
            ]);
    }
}
