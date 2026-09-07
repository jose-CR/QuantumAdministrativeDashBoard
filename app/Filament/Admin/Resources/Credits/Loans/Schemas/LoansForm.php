<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Schemas;

use App\Models\ArticleUnit;
use App\Models\Transportation;
use App\Support\ElSalvadorCatalogo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LoansForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('loan')
                    ->tabs([
                        Tabs\Tab::make('Crédito')
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Cliente')
                                    ->relationship('client', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),

                                Repeater::make('items')
                                    ->label('Bienes / Servicios')
                                    ->schema([
                                        Select::make('item_type')
                                            ->label('Tipo')
                                            ->options([
                                                ArticleUnit::class => 'Vehículo',
                                                Transportation::class => 'Transporte',
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpan(1)
                                            ->afterStateUpdated(function (callable $set, Get $get) {

                                                $oldPrice = (float) ($get('price') ?? 0);

                                                $items = $get('../../items') ?? [];

                                                $total = collect($items)->sum(
                                                    fn ($item) => (float) ($item['price'] ?? 0)
                                                );

                                                $set('../../initial_amount', round($total - $oldPrice, 2));

                                                $set('item_id', null);
                                                $set('price', null);

                                            }),

                                        Select::make('item_id')
                                            ->label(
                                                fn (Get $get) =>
                                                    $get('item_type') === Transportation::class
                                                        ? 'Transporte'
                                                        : 'Vehículo'
                                            )
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->columnSpan(2)

                                            /*
                                             * VEHÍCULOS
                                             */
                                            ->options(function (Get $get) {
                                                if (
                                                    $get('item_type') !==
                                                    ArticleUnit::class
                                                ) {
                                                    return [];
                                                }

                                                return ArticleUnit::query()
                                                    ->with('article')
                                                    ->where('status', 'available')
                                                    ->get()
                                                    ->mapWithKeys(
                                                        fn (ArticleUnit $unit) => [
                                                            $unit->id => $unit->display_name,
                                                        ]
                                                    )
                                                    ->toArray();
                                            })

                                            /*
                                             * TRANSPORTES
                                             */
                                            ->getSearchResultsUsing(
                                                function (
                                                    string $search,
                                                    Get $get
                                                ) {
                                                    if (
                                                        $get('item_type') !==
                                                        Transportation::class
                                                    ) {
                                                        return [];
                                                    }

                                                    $search = mb_strtolower(
                                                        trim($search)
                                                    );

                                                    if ($search === '') {
                                                        return [];
                                                    }

                                                    return Transportation::query()
                                                        ->get()
                                                        ->filter(
                                                            function (
                                                                Transportation $transportation
                                                            ) use ($search) {
                                                                $department =
                                                                    ElSalvadorCatalogo::departmentName(
                                                                        $transportation->department
                                                                    );

                                                                $municipality =
                                                                    ElSalvadorCatalogo::municipalityName(
                                                                        $transportation->department,
                                                                        $transportation->municipality
                                                                    );

                                                                $district =
                                                                    ElSalvadorCatalogo::districtName(
                                                                        $transportation->municipality,
                                                                        $transportation->district
                                                                    );

                                                                $location =
                                                                    sprintf(
                                                                        '%s / %s / %s',
                                                                        $department,
                                                                        $municipality,
                                                                        $district
                                                                    );

                                                                return str_contains(
                                                                    mb_strtolower(
                                                                        $location
                                                                    ),
                                                                    $search
                                                                );
                                                            }
                                                        )
                                                        ->take(50)
                                                        ->mapWithKeys(
                                                            function (
                                                                Transportation $transportation
                                                            ) {
                                                                $department =
                                                                    ElSalvadorCatalogo::departmentName(
                                                                        $transportation->department
                                                                    );

                                                                $municipality =
                                                                    ElSalvadorCatalogo::municipalityName(
                                                                        $transportation->department,
                                                                        $transportation->municipality
                                                                    );

                                                                $district =
                                                                    ElSalvadorCatalogo::districtName(
                                                                        $transportation->municipality,
                                                                        $transportation->district
                                                                    );

                                                                return [
                                                                    $transportation->id =>
                                                                        sprintf(
                                                                            '%s / %s / %s',
                                                                            $department,
                                                                            $municipality,
                                                                            $district
                                                                        ),
                                                                ];
                                                            }
                                                        )
                                                        ->toArray();
                                                }
                                            )

                                            /*
                                             * MOSTRAR EL ITEM SELECCIONADO
                                             */
                                            ->getOptionLabelUsing(
                                                function (
                                                    $value,
                                                    Get $get
                                                ) {
                                                    if (!$value) {
                                                        return null;
                                                    }

                                                    /*
                                                     * VEHÍCULO
                                                     */
                                                    if (
                                                        $get('item_type') ===
                                                        ArticleUnit::class
                                                    ) {
                                                        return ArticleUnit::query()
                                                            ->find($value)
                                                            ?->display_name;
                                                    }

                                                    /*
                                                     * TRANSPORTE
                                                     */
                                                    if (
                                                        $get('item_type') !==
                                                        Transportation::class
                                                    ) {
                                                        return null;
                                                    }

                                                    $transportation =
                                                        Transportation::find(
                                                            $value
                                                        );

                                                    if (!$transportation) {
                                                        return null;
                                                    }

                                                    $department =
                                                        ElSalvadorCatalogo::departmentName(
                                                            $transportation->department
                                                        );

                                                    $municipality =
                                                        ElSalvadorCatalogo::municipalityName(
                                                            $transportation->department,
                                                            $transportation->municipality
                                                        );

                                                    $district =
                                                        ElSalvadorCatalogo::districtName(
                                                            $transportation->municipality,
                                                            $transportation->district
                                                        );

                                                    return sprintf(
                                                        '%s / %s / %s',
                                                        $department,
                                                        $municipality,
                                                        $district
                                                    );
                                                }
                                            )

                                            /*
                                             * OBTENER PRECIO Y ACTUALIZAR
                                             * EL MONTO INICIAL.
                                             */
                                            ->afterStateUpdated(function (
                                                $state,
                                                callable $set,
                                                Get $get
                                            ) {

                                                if (!$state) {
                                                    $set('price', null);
                                                    return;
                                                }

                                                $price = match ($get('item_type')) {

                                                    ArticleUnit::class =>
                                                        (float) (ArticleUnit::find($state)?->cash_price ?? 0),

                                                    Transportation::class =>
                                                        (float) (Transportation::find($state)?->price ?? 0),

                                                    default => 0,
                                                };

                                                $set('price', $price);

                                                $set(
                                                    '../../initial_amount',
                                                    round(
                                                        collect($get('../../items') ?? [])
                                                            ->map(function ($item) use ($state, $price, $get) {

                                                                $sameItem =
                                                                    ($item['item_type'] ?? null) === $get('item_type')
                                                                    &&
                                                                    ($item['item_id'] ?? null) == $state;

                                                                return $sameItem
                                                                    ? $price
                                                                    : (float) ($item['price'] ?? 0);
                                                            })
                                                            ->sum(),
                                                        2
                                                    )
                                                );

                                            }),

                                        /*
                                         * PRECIO
                                         */
                                        TextInput::make('price')
                                            ->label('Precio')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->readOnly()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->required()
                                    ->minItems(1)
                                    ->columnSpanFull()
                                    ->live(),
                                /*
                                 * MONTO INICIAL
                                 */
                                TextInput::make('initial_amount')
                                    ->label('Monto inicial')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('down_payment')
                                    ->label('Pago inicial')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->required(),

                                TextInput::make('installments')
                                    ->label('Número de cuotas')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),

                                TextInput::make('installment_amount')
                                    ->label('Monto de cuota')
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
                                    ->label('Fecha de inicio')
                                    ->required(),

                                TextInput::make('payment_day')
                                    ->label('Día de pago')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->required(),

                                Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'completed' => 'Completado',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('active')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}