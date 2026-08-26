<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Schemas;

use App\Models\ArticleUnit;
use App\Models\Client;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                                        Section::make()
                                            ->description('Información del cliente y del vehículo asociado al crédito')
                                            ->schema([
                                                Select::make('customer_id')
                                                    ->label(__('resources.credits.clients.client'))
                                                    ->relationship('client', 'full_name')
                                                    ->getOptionLabelFromRecordUsing(
                                                        fn (Customer $record): string => $record->full_name
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),

                                                Repeater::make('items')
                                                    ->label('Vehículo')
                                                    ->relationship('items')
                                                    ->schema([
                                                        Select::make('article_unit_id')
                                                            ->label(__('resources.credits.clients.vehicle'))
                                                            ->relationship('articleUnit', 'id')
                                                            ->getOptionLabelFromRecordUsing(
                                                                fn (ArticleUnit $record): string => $record->display_name
                                                            )
                                                            ->searchable()
                                                                ->getSearchResultsUsing(function (string $search): array {
                                                                        return ArticleUnit::query()
                                                                            ->with('article')
                                                                            ->where(function ($query) use ($search) {
                                                                                $query
                                                                                    ->where('vin', 'ILIKE', "%{$search}%")
                                                                                    ->orWhere('color', 'ILIKE', "%{$search}%")
                                                                                    ->orWhere('plate', 'ILIKE', "%{$search}%")
                                                                                    ->orWhereHas('article', function ($query) use ($search) {
                                                                                        $query
                                                                                            ->where('brand', 'ILIKE', "%{$search}%")
                                                                                            ->orWhere('model', 'ILIKE', "%{$search}%");
                                                                                    });
                                                                            })
                                                                            ->limit(50)
                                                                            ->get()
                                                                            ->mapWithKeys(fn (ArticleUnit $unit) => [
                                                                                $unit->id => $unit->display_name,
                                                                            ])
                                                                            ->toArray();
                                                                    })
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                $articleUnit = ArticleUnit::find($state);

                                                                $set('price', $articleUnit?->cash_price);
                                                            })
                                                            ->preload()
                                                            ->required(),

                                                        TextInput::make('price')
                                                            ->label('Precio')
                                                            ->numeric()
                                                            ->prefix('$')
                                                            ->disabled()
                                                            ->required(),
                                                    ])
                                                    ->columns(2)
                                                    ->minItems(1)
                                                    ->maxItems(10)
                                                    ->defaultItems(1)
                                                    ->addable(true)
                                                    ->deletable(true)
                                                    ->reorderable(false),

                                                Select::make('assigned_user_id')
                                                    ->label(__('resources.alert.assigned_user'))
                                                    ->options(
                                                        User::query()->pluck('name', 'id')
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Financiamiento')
                            ->schema([
                                Grid::make(3)
                                    ->schema([

                                        TextInput::make('initial_amount')
                                            ->label(__('resources.credits.credits.initial_amount'))
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
