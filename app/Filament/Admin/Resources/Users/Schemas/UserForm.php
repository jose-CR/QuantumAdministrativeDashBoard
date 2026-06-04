<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label("usuario")
                    ->required(),
                TextInput::make('password')
                    ->label('contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                TextInput::make('email')
                    ->email()
                    ->unique()
                    ->required(),
                //
            ]);
    }
}
