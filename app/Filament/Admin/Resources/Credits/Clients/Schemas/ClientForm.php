<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Schemas;

use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Utils\Filament\FilamentSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Cliente')
                    ->tabs([
                        Tab::make('Información Personal')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('full_name')
                                            ->label('Nombre completo')
                                            ->required(),

                                        TextInput::make('identity_document')
                                            ->label('DUI')
                                            ->placeholder('12345678-9')
                                            ->required()
                                            ->unique(ignoreRecord: true),

                                        DatePicker::make('birth_date')
                                            ->label('Fecha de nacimiento')
                                            ->required(),

                                        Select::make('gender')
                                            ->label('Género')
                                            ->options([
                                                'male' => 'Masculino',
                                                'female' => 'Femenino',
                                            ])
                                            ->required(),

                                        Select::make('marital_status')
                                            ->label('Estado civil')
                                            ->options([
                                                'single' => 'Soltero',
                                                'married' => 'Casado',
                                                'divorced' => 'Divorciado',
                                                'widowed' => 'Viudo',
                                            ]),

                                        TextInput::make('nationality')
                                            ->label('Nacionalidad'),
                                    ])
                            ]),

                        Tab::make('Contacto')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('phone_primary')
                                            ->label('Teléfono principal')
                                            ->tel()
                                            ->required(),

                                        TextInput::make('phone_secondary')
                                            ->label('Teléfono secundario')
                                            ->tel(),

                                        TextInput::make('email')
                                                ->label('Correo')
                                                ->email(),

                                        TextInput::make('address')
                                            ->label('Dirección')
                                            ->columnSpanFull()
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('Referencias')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->schema([
                                Repeater::make('references')
                                    ->relationship()
                                    ->label('Referencias')
                                    ->addActionLabel('Agregar referencia')
                                    ->cloneable()
                                    ->itemLabel(
                                        fn (array $state): ?string =>
                                                $state['full_name'] ?? 'Nueva referencia'
                                    )
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('full_name')
                                                    ->label('Nombre completo')
                                                    ->required(),

                                                Select::make('reference_type')
                                                    ->label('Tipo')
                                                    ->options([
                                                        'family' => 'Familiar',
                                                        'friend' => 'Amigo',
                                                    ])
                                                    ->required(),

                                                TextInput::make('relationship')
                                                    ->label('Parentesco'),

                                                TextInput::make('phone')
                                                    ->label('Teléfono')
                                                    ->tel()
                                                    ->required(),

                                                TextInput::make('occupation')
                                                    ->label('Ocupación'),

                                                TextInput::make('address')
                                                    ->label('Dirección')
                                                    ->columnSpanFull(),

                                            ])
                                    ])
                            ]),
                        Tab::make('Crédito')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('article_unit_id')
                                            ->label('Artículo')
                                            ->options(
                                                FilamentSelect::options(
                                                    ArticleUnit::class,
                                                    [
                                                        'display_name',
                                                    ]
                                                )
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set){
                                                $articleUnit = ArticleUnit::with('article')->find($state);

                                                $set(
                                                    'initial_amount',
                                                    $articleUnit?->article?->cash_price
                                                );
                                            })
                                            ->required(),

                                        TextInput::make('initial_amount')
                                            ->label('Monto Inicial')
                                            ->readOnly()
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
                                            ->required(),

                                        Select::make('status')
                                            ->label('Estado')
                                            ->options([
                                                'pending' => 'Pendiente',
                                                'active' => 'Activo',
                                                'paid' => 'Pagado',
                                                'cancelled' => 'Cancelado',
                                                'completed' => 'Completado',
                                            ])
                                            ->default('active')
                                            ->required(),

                                        Select::make('refinanced_from_id')
                                            ->label('Refinanciar Crédito')
                                            ->options(
                                                Credit::query()
                                                    ->pluck('id', 'id')
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                    ]),
                            ]),
                        ])
                    ->columnSpanFull(),
            ]);
    }
}
