<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class InstallmentsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label('Monto de la cuota')
                    ->numeric()
                    ->required(),

                TextInput::make('remaining_balance')
                    ->label('Saldo restante')
                    ->numeric()
                    ->required(),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'late' => 'Atrasado',
                        'cancelled' => 'Cancelado',
                        'completed' => 'Conpletado',
                    ])
                    ->required(),

                TextInput::make('number')
                    ->label('Número de cuota')
                    ->numeric()
                    ->disabled(),

                TextInput::make('due_date')
                    ->label('Fecha de vencimiento')
                    ->disabled(),
            ]);
    }
}
