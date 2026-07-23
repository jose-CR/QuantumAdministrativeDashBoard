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
                    ->label(__('resources.credits.installment.amount'))
                    ->numeric()
                    ->required(),

                TextInput::make('remaining_balance')
                    ->label(__('resources.credits.installment.remaining_balance'))
                    ->numeric()
                    ->required(),

                Select::make('status')
                    ->label(__('resources.credits.installment.status'))
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'late' => 'Atrasado',
                        'cancelled' => 'Cancelado',
                        'completed' => 'Completado',
                    ])
                    ->required(),

                TextInput::make('number')
                    ->label(__('resources.credits.installment.number'))
                    ->numeric()
                    ->disabled(),

                TextInput::make('due_date')
                    ->label(__('resources.credits.installment.paid_at'))
                    ->disabled(),

                // Datos del pago
                TextInput::make('paid_amount')
                    ->label(__('resources.credits.installment.paid_amount'))
                    ->numeric(),

                Select::make('payment_method')
                    ->label(__('resources.credits.payment_histories.payment_method'))
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'bank_transfer' => 'Transferencia bancaria',
                    ])
                    ->live(),

                Select::make('bank_id')
                    ->label(__('resources.credits.payment_histories.bank'))
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
                    ->label(__('resources.credits.payment_histories.receipt_number')),

                DatePicker::make('payment_date')
                    ->label(__('resources.credits.payment_histories.payment_date'))
                    ->default(now()),
            ]);
    }
}
