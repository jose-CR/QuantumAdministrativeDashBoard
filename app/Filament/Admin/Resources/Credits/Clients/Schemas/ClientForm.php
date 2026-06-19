<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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
                                        TextInput::make('first_name')
                                            ->label('Nombre')
                                            ->required(),

                                        TextInput::make('last_name')
                                            ->label('Apellido')
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
                                                    ->label('Parentesco')
                                                    ->required(),



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

                    ])
                    ->columnSpanFull(),
            ]);
    }
}



/*
        Tab::make('Información Laboral')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('occupation')
                            ->label('Ocupación'),

                        TextInput::make('workplace')
                            ->label('Lugar de trabajo'),

                        TextInput::make('monthly_income')
                            ->label('Ingreso mensual')
                            ->numeric()
                            ->prefix('$'),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
            ]),
*/
