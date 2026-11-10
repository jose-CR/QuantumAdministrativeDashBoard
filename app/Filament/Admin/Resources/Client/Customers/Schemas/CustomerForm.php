<?php

namespace App\Filament\Admin\Resources\Client\Customers\Schemas;

use App\Models\Customer;
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
use Filament\Schemas\Components\Utilities\Set;
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
                                            // La lista sale del modelo: el import y la
                                            // validación usan la misma.
                                            ->options(collect(Customer::DOCUMENT_TYPES)
                                                ->mapWithKeys(fn (string $tipo): array => [
                                                    $tipo => match ($tipo) {
                                                        'RES CARNET' => __('resources.clients.fields.document_type.RES_CARNET'),
                                                        'OTRO' => __('resources.clients.fields.document_type.otro'),
                                                        default => __('resources.clients.fields.document_type.' . $tipo),
                                                    },
                                                ])
                                                ->all())
                                            ->live()
                                            // Cambiar el tipo cambia la máscara: el número
                                            // cargado ya no sirve, se limpia.
                                            ->afterStateUpdated(fn (Set $set) => $set('document_number', null))
                                            ->required(),

                                        TextInput::make('document_number')
                                            ->label(__('resources.clients.fields.document_number'))
                                            ->mask(fn (Get $get) =>
                                                DocumentHelper::mask(
                                                    $get('document_type')
                                                )
                                            )
                                            // La identidad es tipo + número: el mismo número
                                            // con otro tipo es otro cliente.
                                            ->unique(
                                                table: 'customers',
                                                column: 'document_number',
                                                ignoreRecord: true,
                                                modifyRuleUsing: fn ($rule, Get $get) => $rule
                                                    ->where('document_type', $get('document_type')),
                                            )
                                            ->validationMessages([
                                                'unique' => 'Ya hay un cliente con ese documento.',
                                            ])
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

                    ])
                    ->columnSpanFull(),
            ]);
    }
}