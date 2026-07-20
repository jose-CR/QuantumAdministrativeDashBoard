<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Schemas;

use App\Models\Bank;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
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
                        'completed' => 'Completado',
                    ])
                    ->required(),

                TextInput::make('number')
                    ->label('Número de cuota')
                    ->numeric()
                    ->disabled(),

                TextInput::make('due_date')
                    ->label('Fecha de vencimiento')
                    ->disabled(),

                // Datos del pago
                TextInput::make('paid_amount')
                    ->label('Monto abonado')
                    ->numeric(),

                Select::make('payment_method')
                    ->label('Método de pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'bank_transfer' => 'Transferencia bancaria',
                    ])
                    ->live(),

                Select::make('bank_id')
                    ->label('Banco')
                    ->options(
                        Bank::pluck('name', 'id')
                    )
                    ->visible(
                        fn (Get $get) => $get('payment_method') !== 'cash'
                    )
                    ->required(
                        fn (Get $get) => $get('payment_method') !== 'cash'
                    ),

                TextInput::make('receipt_number')
                    ->label('Número de comprobante'),

                DatePicker::make('payment_date')
                    ->label('Fecha del pago')
                    ->default(now()),
            ]);
    }
}
