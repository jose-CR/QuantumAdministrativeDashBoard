<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Schemas;

use App\Models\ArticleUnit;
use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LoansForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Crédito')
                    ->tabs([

                        Tab::make('Cliente y Vehículo')
                            ->schema([
                                Grid::make(2)
                                    ->schema([

                                        Select::make('client_id')
                                            ->label('Cliente')
                                            ->relationship('client', 'first_name')
                                            ->getOptionLabelFromRecordUsing(
                                                fn (Client $record): string =>
                                                    "{$record->first_name} {$record->last_name}"
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('article_unit_id')
                                            ->label('Vehículo')
                                            ->relationship('articleUnit', 'vin')
                                            ->getOptionLabelFromRecordUsing(
                                                fn (ArticleUnit $record): string =>
                                                    $record->display_name
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('Financiamiento')
                            ->schema([
                                Grid::make(3)
                                    ->schema([

                                        TextInput::make('initial_amount')
                                            ->label('Monto Inicial')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('down_payment')
                                            ->label('Prima')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('installments')
                                            ->label('Número de Cuotas')
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('installment_amount')
                                            ->label('Valor Cuota')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        Select::make('periodicity')
                                            ->label('Periodicidad')
                                            ->options([
                                                'weekly' => 'Semanal',
                                                'biweekly' => 'Quincenal',
                                                'monthly' => 'Mensual',
                                            ])
                                            ->required(),

                                        DatePicker::make('start_date')
                                            ->label('Fecha Inicio')
                                            ->required(),

                                        TextInput::make('payment_day')
                                            ->label('Día de Pago')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(31)
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('Refinanciamiento')
                            ->schema([

                                Select::make('refinanced_from_id')
                                    ->label('Crédito Anterior')
                                    ->relationship(
                                        'originalCredit',
                                        'id'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                            ]),

                        Tab::make('Estado')
                            ->schema([

                                Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'active' => 'Activo',
                                        'paid' => 'Pagado',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('pending')
                                    ->required(),

                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}