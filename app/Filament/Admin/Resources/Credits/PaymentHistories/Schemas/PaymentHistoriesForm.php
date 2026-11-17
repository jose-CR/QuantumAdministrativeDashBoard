<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Schemas;

use App\Models\Bank;
use App\Models\Credit;
use App\Utils\Filament\FilamentSearch;
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
                FilamentSearch::searchable(
                    Select::make('credit_id')
                        ->required(),
                    Credit::query()->with('client'),
                    ['id'],
                    fn (Credit $credito): string => "#{$credito->id} - {$credito->client?->full_name}",
                    castColumns: ['id'],
                    relations: ['client' => ['full_name']],
                )
                    // Cambiar el crédito de un pago dejaría el crédito viejo sin
                    // recalcular: se corrige con un pago nuevo, no moviéndolo.
                    ->disabledOn('edit'),

                /*
                 * El monto SÍ se puede corregir: el modelo recalcula las cuotas y el
                 * saldo cuando cambia (ver PaymentHistory::booted). Lo que no se toca
                 * es el crédito ni la fecha, que sí dejarían el historial descuadrado.
                 */
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->helperText(__('ui.payments.amount_edit_help')),

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
                    ->required()
                    // Cambiar la fecha de un pago viejo descuadra el historial sin
                    // recalcular nada.
                    ->disabledOn('edit'),

                TextInput::make('receipt_number')
                    ->maxLength(255),

                RichEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
