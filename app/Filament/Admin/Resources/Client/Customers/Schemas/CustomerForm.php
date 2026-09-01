<?php

namespace App\Filament\Admin\Resources\Client\Customers\Schemas;

use App\Rules\DepartmentCodeRule;
use App\Rules\DistrictCodeRule;
use App\Rules\MunicipalityCodeRule;
use App\Support\ActividadesEconomicas;
use App\Support\DocumentHelper;
use App\Support\ElSalvadorCatalogo;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('client')
                    ->tabs([
                        Tab::make('personal information')
                            ->schema([
                                Grid::make(2)
                                    ->schema([

                                        // Documento
                                        Select::make('document_type')
                                            ->label('Tipo de documento')
                                            ->options([
                                                'DUI' => 'DUI',
                                                'NIT' => 'NIT',
                                                'Passport' => 'Passport',
                                                'Carnet RES' => 'Carnet RES',
                                                'OTRO' => 'OTRO',
                                            ])
                                            ->live()
                                            ->required(),

                                        TextInput::make('document_number')
                                            ->label('Número de documento')
                                            ->key(fn (Get $get) =>
                                                'document_number_' . $get('document_type')
                                            )
                                            ->mask(fn (Get $get) =>
                                                DocumentHelper::mask(
                                                    $get('document_type')
                                                )
                                            )
                                            ->required(),

                                        // Información personal
                                        TextInput::make('full_name')
                                            ->label('Nombre completo')
                                            ->required(),

                                        TextInput::make('email')
                                            ->label('Correo electrónico')
                                            ->email(),

                                        TextInput::make('phone_primary')
                                            ->label('Teléfono principal'),

                                        TextInput::make('phone_secondary')
                                            ->label('Teléfono secundario'),

                                        TextInput::make('nrc')
                                            ->label('NRC'),

                                        // Actividad económica
                                        Select::make('economic_activity')
                                            ->label('Actividad económica')
                                            ->options(
                                                ActividadesEconomicas::options()
                                            )
                                            ->searchable()
                                            ->preload(),

                                        // Departamento
                                        Select::make('department')
                                            ->label('Departamento')
                                            ->options(
                                                ElSalvadorCatalogo::departments()
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->rules([
                                                new DepartmentCodeRule
                                            ])
                                            ->afterStateUpdated(function (callable $set) {
                                                $set('municipality', null);
                                                $set('district', null);
                                            }),

                                        // Municipio
                                        Select::make('municipality')
                                            ->label('Municipio')
                                            ->options(function (Get $get) {
                                                $department = $get('department');

                                                if (!$department) {
                                                    return [];
                                                }

                                                return ElSalvadorCatalogo::municipalities(
                                                    $department
                                                );
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()                                            
                                            ->rules(function (Get $get) {
                                                $department = $get('department');

                                                if (!$department) {
                                                    return [];
                                                }

                                                return [
                                                    new MunicipalityCodeRule($department),
                                                ];
                                            })
                                            ->disabled(fn (Get $get) => !$get('department'))
                                            ->afterStateUpdated(function (callable $set) {
                                                $set('district', null);
                                            }),

                                        // Distrito
                                        Select::make('district')
                                            ->label('Distrito')
                                            ->options(function (Get $get) {
                                                $municipality = $get('municipality');

                                                if (!$municipality) {
                                                    return [];
                                                }

                                                return ElSalvadorCatalogo::districts(
                                                    $municipality
                                                );
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->rules(function (Get $get) {
                                                $municipality = $get('municipality');

                                                if (!$municipality) {
                                                    return [];
                                                }

                                                return [
                                                    new DistrictCodeRule($municipality),
                                                ];
                                            })
                                            ->disabled(fn (Get $get) => !$get('municipality')),

                                        // Dirección
                                        TextInput::make('address')
                                            ->label('Dirección')
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('references')
                            ->visible(function (Get $get, string $operation): bool {
                                return $operation === 'create'
                                    && $get('document_type') === 'DUI';
                            })
                            ->schema([
                                Repeater::make('references')
                                    ->relationship()
                                    ->label(__('resources.clients.sections.references'))
                                    ->addActionLabel(__('resources.clients.actions.add_reference'))
                                    ->cloneable()
                                    ->itemLabel(
                                        fn (array $state): ?string =>
                                            $state['full_name']
                                                ?? __('resources.clients.messages.new_reference')
                                    )
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('full_name')
                                                    ->label(__('resources.clients.fields.full_name'))
                                                    ->required(),

                                                Select::make('reference_type')
                                                    ->label(__('resources.clients.fields.reference_type'))
                                                    ->options([
                                                        'family' => __('resources.clients.reference_types.family'),
                                                        'friend' => __('resources.clients.reference_types.friend'),
                                                    ])
                                                    ->required(),

                                                TextInput::make('relationship')
                                                    ->label(__('resources.clients.fields.relationship')),

                                                TextInput::make('phone')
                                                    ->label(__('resources.clients.fields.phone'))
                                                    ->tel()
                                                    ->required(),

                                                TextInput::make('occupation')
                                                    ->label(__('resources.clients.fields.occupation')),

                                                TextInput::make('address')
                                                    ->label(__('resources.clients.fields.address'))
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}