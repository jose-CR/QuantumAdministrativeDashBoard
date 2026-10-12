<?php

namespace App\Filament\Admin\Resources\Client\Customers\Schemas;

use App\Rules\DepartmentCodeRule;
use App\Rules\DistrictCodeRule;
use App\Rules\MunicipalityCodeRule;
use App\Support\ActividadesEconomicas;
use App\Support\DocumentHelper;
use App\Support\ElSalvadorCatalogo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
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
                                            ->label(__('resources.clients.fields.document_type.document_type'))
                                            ->options([
                                                'DUI' => __('resources.clients.fields.document_type.DUI'),
                                                'NIT' => __('resources.clients.fields.document_type.NIT'),
                                                'PASSPORT' => __('resources.clients.fields.document_type.PASSPORT'),
                                                'RES CARNET' => __('resources.clients.fields.document_type.RES_CARNET'),
                                                'OTRO' => __('resources.clients.fields.document_type.otro'),
                                            ])
                                            ->live()
                                            ->required(),

                                        TextInput::make('document_number')
                                            ->label(__('resources.clients.fields.document_number'))
                                            ->key(fn (Get $get) =>
                                                'document_number_' . $get('document_type')
                                            )
                                            ->mask(fn (Get $get) =>
                                                DocumentHelper::mask(
                                                    $get('document_type')
                                                )
                                            )
                                            ->required(),
                                        
                                        SpatieTagsInput::make('tags')
                                            ->type('customer')
                                            ->label(__('resources.clients.fields.tags.tags')),

                                        // Información personal
                                        TextInput::make('full_name')
                                            ->label(__('resources.clients.fields.full_name'))
                                            ->required(),

                                        TextInput::make('email')
                                            ->label(__('resources.users.email'))
                                            ->email(),

                                        TextInput::make('phone_primary')
                                            ->label(__('resources.clients.fields.phone_primary'))
                                            ->mask(fn () => DocumentHelper::mask('PHONE')),

                                        TextInput::make('phone_secondary')
                                            ->label(__('resources.clients.fields.phone_secondary'))
                                            ->mask(fn () => DocumentHelper::mask('PHONE')),

                                        TextInput::make('nrc')
                                            ->label(__('resources.clients.fields.enterprise.nrc')),

                                        // Actividad económica
                                        Select::make('economic_activity')
                                            ->label(__('resources.clients.fields.enterprise.economic_activity'))
                                            ->options(
                                                ActividadesEconomicas::options()
                                            )
                                            ->searchable()
                                            ->preload(),

                                        // Departamento
                                        Select::make('department')
                                            ->label(__('resources.clients.fields.enterprise.departament'))
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
                                            })
                                            ->required(),

                                        // Municipio
                                        Select::make('municipality')
                                            ->label(__('resources.clients.fields.enterprise.municipality'))
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
                                            })
                                            ->required(),

                                        // Distrito
                                        Select::make('district')
                                            ->label(__('resources.clients.fields.enterprise.distric'))
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
                                            ->disabled(fn (Get $get) => !$get('municipality'))
                                            ->required(),

                                        // Dirección
                                        TextInput::make('address')
                                            ->label(__('resources.clients.fields.address'))
                                            ->required(),
                                    ]),
                            ]),

/*                         Tab::make('references')
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
                            ]), */
                    ])
                    ->columnSpanFull(),
            ]);
    }
}