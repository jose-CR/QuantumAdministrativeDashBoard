<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Schemas;

use App\Models\Article;
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
    /**
     * Suma del repetidor de bienes: precio unitario por cantidad.
     *
     * La fila que se está eligiendo puede venir en $type/$id porque el estado del
     * formulario todavía no la tiene: si coincide, se usa el precio/cantidad nuevos.
     *
     * @param  iterable<int|string, array<string, mixed>>  $items
     */
    protected static function itemsTotal(
        iterable $items,
        ?string $type = null,
        int|string|null $id = null,
        ?float $price = null,
        ?int $quantity = null,
    ): float {
        $total = 0.0;

        foreach ($items as $item) {
            $esLaFila = $type !== null
                && ($item['item_type'] ?? null) === $type
                && (string) ($item['item_id'] ?? '') === (string) $id;

            $precio = $esLaFila && $price !== null ? $price : (float) ($item['price'] ?? 0);
            $cantidad = $esLaFila && $quantity !== null ? $quantity : (int) ($item['quantity'] ?? 1);

            $total += $precio * max(1, $cantidad);
        }

        return round($total, 2);
    }

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
                                    ->relationship('items')
                                    ->label('Bienes / Servicios')
                                    ->schema([
                                        Select::make('item_type')
                                            ->label('Tipo')
                                            ->options([
                                                ArticleUnit::class => 'Vehículo',
                                                Article::class => 'Artículo (por cantidad)',
                                                Transportation::class => 'Transporte',
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpan(1)
                                            ->afterStateUpdated(function (callable $set, Get $get) {

                                                $oldAmount = (float) ($get('price') ?? 0)
                                                    * max(1, (int) ($get('quantity') ?? 1));

                                                $items = $get('../../items') ?? [];

                                                $set(
                                                    '../../initial_amount',
                                                    round(self::itemsTotal($items) - $oldAmount, 2)
                                                );

                                                $set('item_id', null);
                                                $set('price', null);
                                                $set('quantity', 1);

                                            }),

                                        Select::make('item_id')
                                            ->label(
                                                fn (Get $get) => match ($get('item_type')) {
                                                    Transportation::class => 'Transporte',
                                                    Article::class => 'Artículo',
                                                    default => 'Vehículo',
                                                }
                                            )
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->columnSpan(2)

                                            /*
                                             * BÚSQUEDA (VEHÍCULOS Y TRANSPORTES EN UNA SOLA FUNCIÓN)
                                             *
                                             * IMPORTANTE: getSearchResultsUsing() NO es aditivo.
                                             * Solo puede haber UNA llamada a este método por Select;
                                             * si se llama dos veces, la segunda sobrescribe a la
                                             * primera por completo. Por eso ambos casos (vehículos
                                             * y transportes) van dentro del mismo closure.
                                             */
                                            ->getSearchResultsUsing(function (string $search, Get $get) {

                                                $search = mb_strtolower(trim($search));

                                                if ($search === '') {
                                                    return [];
                                                }

                                                /*
                                                 * VEHÍCULOS
                                                 */
                                                if ($get('item_type') === ArticleUnit::class) {
                                                    return ArticleUnit::query()
                                                        ->with('article')
                                                        ->where('status', 'available')
                                                        ->where(function ($query) use ($search) {
                                                            $query->where('vin', 'ilike', "%{$search}%")
                                                                ->orWhere('color', 'ilike', "%{$search}%")
                                                                ->orWhere('plate', 'ilike', "%{$search}%")
                                                                ->orWhereHas('article', function ($query) use ($search) {
                                                                    // `name` cubre los artículos serializados que no
                                                                    // son vehículos (batería con serie, por ejemplo).
                                                                    $query->where('name', 'ilike', "%{$search}%")
                                                                        ->orWhere('sku', 'ilike', "%{$search}%")
                                                                        ->orWhere('brand', 'ilike', "%{$search}%")
                                                                        ->orWhere('model', 'ilike', "%{$search}%");
                                                                });
                                                        })
                                                        ->get()
                                                        ->mapWithKeys(fn (ArticleUnit $unit) => [
                                                            $unit->id => $unit->display_name,
                                                        ])
                                                        ->toArray();
                                                }

                                                /*
                                                 * TRANSPORTES
                                                 */
                                                if ($get('item_type') === Transportation::class) {
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

                                                /*
                                                 * ARTÍCULOS POR CANTIDAD
                                                 * (cascos, baterías sueltas: no tienen serie)
                                                 */
                                                if ($get('item_type') === Article::class) {
                                                    return Article::query()
                                                        ->where('tracking', Article::TRACKING_NONE)
                                                        ->where('stock', '>', 0)
                                                        ->where(function ($query) use ($search) {
                                                            $query->where('name', 'ilike', "%{$search}%")
                                                                ->orWhere('sku', 'ilike', "%{$search}%")
                                                                ->orWhere('brand', 'ilike', "%{$search}%")
                                                                ->orWhere('model', 'ilike', "%{$search}%");
                                                        })
                                                        ->orderBy('name')
                                                        ->take(50)
                                                        ->get()
                                                        ->mapWithKeys(fn (Article $article) => [
                                                            $article->id => "{$article->full_name} (stock: {$article->stock})",
                                                        ])
                                                        ->toArray();
                                                }

                                                return [];
                                            })

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
                                                     * ARTÍCULO POR CANTIDAD
                                                     */
                                                    if (
                                                        $get('item_type') ===
                                                        Article::class
                                                    ) {
                                                        return Article::query()
                                                            ->find($value)
                                                            ?->full_name;
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

                                                    Article::class =>
                                                        (float) (Article::find($state)?->cash_price ?? 0),

                                                    Transportation::class =>
                                                        (float) (Transportation::find($state)?->price ?? 0),

                                                    default => 0,
                                                };

                                                $set('price', $price);

                                                // El monto del crédito es precio unitario por cantidad.
                                                $set(
                                                    '../../initial_amount',
                                                    self::itemsTotal(
                                                        $get('../../items') ?? [],
                                                        $get('item_type'),
                                                        $state,
                                                        $price,
                                                    )
                                                );

                                            }),

                                        /*
                                         * CANTIDAD
                                         *
                                         * Solo para artículos que se llevan por cantidad. El máximo
                                         * es lo que hay en existencia: no se puede vender lo que no está.
                                         */
                                        TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->integer()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(fn (Get $get) => max(
                                                1,
                                                (int) (Article::query()->find($get('item_id'))?->stock ?? 1)
                                            ))
                                            ->visible(fn (Get $get) => $get('item_type') === Article::class)
                                            ->required(fn (Get $get) => $get('item_type') === Article::class)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                $set(
                                                    '../../initial_amount',
                                                    self::itemsTotal(
                                                        $get('../../items') ?? [],
                                                        $get('item_type'),
                                                        $get('item_id'),
                                                        (float) ($get('price') ?? 0),
                                                        (int) ($state ?? 1),
                                                    )
                                                );
                                            })
                                            ->columnSpanFull(),

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
                                    ->maxValue((int) config('quantum.installments.max', 100))
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