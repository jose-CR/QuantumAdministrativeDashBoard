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
                                            ->label(__('resources.credits.clients.client'))
                                            ->relationship('client', 'full_name')
                                            ->getOptionLabelFromRecordUsing(
                                                fn (Client $record): string =>
                                                    "{$record->full_name}"
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('article_unit_id')
                                            ->label(__('resources.credits.clients.vehicle'))
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
                                            ->label(__('resources.credits.credits.installment_amount'))
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('down_payment')
                                                ->label(__('resources.credits.credits.down_payment'))
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('installments')
                                            ->label(__('resources.credits.credits.installments'))
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('installment_amount')
                                            ->label(__('resources.credits.credits.installment_amount'))
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        Select::make('periodicity')
                                            ->label(__('resources.credits.credits.periodicity'))
                                            ->options([
                                                'weekly' => 'Semanal',
                                                'biweekly' => 'Quincenal',
                                                'monthly' => 'Mensual',
                                            ])
                                            ->required(),

                                        DatePicker::make('start_date')
                                            ->label(__('resources.credits.credits.start_date'))
                                            ->required(),

                                        TextInput::make('payment_day')
                                            ->label(__('resources.credits.credits.payment_day'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(31)
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('Refinanciamiento')
                            ->schema([

                                Select::make('refinanced_from_id')
                                    ->label(__('resources.credits.clients.refinanced'))
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
                                    ->label(__('resources.credits.clients.status'))
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'active' => 'Activo',
                                        'paid' => 'Pagado',
                                        'cancelled' => 'Cancelado',
                                        'completed' => 'Completado'
                                    ])
                                    ->default('pending')
                                    ->required(),

                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
