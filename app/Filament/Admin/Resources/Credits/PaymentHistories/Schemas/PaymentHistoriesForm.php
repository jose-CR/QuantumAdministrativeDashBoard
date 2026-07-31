<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Schemas;

use App\Models\Bank;
use App\Models\Credit;
use App\Utils\Filament\FilamentSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentHistoriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('credit_id')
                    ->options(
                        FilamentSelect::options(
                            Credit::class,
                            ['id', 'client.full_name']
                        )
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('amount')
                    ->numeric()
                    ->required(),

                Select::make('payment_method')
                    ->label(__('resources.credits.clients.pay_installment.payment_method'))
                    ->options([
                        'cash' => __('resources.credits.clients.pay_installment.payment_methods.cash'),
                        'card' => __('resources.credits.clients.pay_installment.payment_methods.card'),
                        'bank_transfer' => __('resources.credits.clients.pay_installment.payment_methods.bank_transfer'),
                    ])
                    ->live()
                    ->required(),

                Select::make('bank_id')
                    ->options(
                        FilamentSelect::options(
                            Bank::class,
                            ['name']
                        )
                    )
                    ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                    ->required(fn ($get) => $get('payment_method') === 'bank_transfer'),

                DatePicker::make('payment_date')
                    ->default(now())
                    ->required(),

                TextInput::make('receipt_number')
                    ->maxLength(255),

                RichEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
