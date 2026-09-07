<?php

namespace App\Filament\Admin\Resources\Inventory\Trasportations\Tables;

use App\Support\ElSalvadorCatalogo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrasportationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department')
                    ->formatStateUsing(fn ($state) => ElSalvadorCatalogo::departmentName($state)    
                    )
                    ->searchable(query: fn (Builder $query, string $search) => 
                        ElSalvadorCatalogo::applyDepartmentSearch($query, $search)
                    ),

                TextColumn::make('municipality')
                    ->formatStateUsing(fn ($state, $record) => 
                        ElSalvadorCatalogo::municipalityName($record->department, $state)      
                    )
                    ->searchable(query: fn (Builder $query, string $search) => 
                        ElSalvadorCatalogo::applyMunicipalitySearch($query, $search)
                    ),

                TextColumn::make('district')
                    ->formatStateUsing(fn ($state, $record) => 
                        ElSalvadorCatalogo::districtName($record->municipality, $state)
                    )
                    ->searchable(query: fn (Builder $query, string $search) => 
                        ElSalvadorCatalogo::applyDistrictSearch($query, $search)
                    ),

                TextColumn::make('price')
                ->money('USD'),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
