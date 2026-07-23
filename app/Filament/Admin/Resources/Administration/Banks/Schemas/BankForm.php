<?php

namespace App\Filament\Admin\Resources\Administration\Banks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                        ->label(__('resources.clients.fields.bank')),
                //
            ]);
    }
}
