<?php

namespace App\Filament\Admin\Resources\Inventory\Trasportations\Schemas;

use App\Rules\DepartmentCodeRule;
use App\Rules\DistrictCodeRule;
use App\Rules\MunicipalityCodeRule;
use App\Support\ElSalvadorCatalogo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TrasportationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    
                TextInput::make('price')
                    ->numeric(),
            ]);
    }
}
