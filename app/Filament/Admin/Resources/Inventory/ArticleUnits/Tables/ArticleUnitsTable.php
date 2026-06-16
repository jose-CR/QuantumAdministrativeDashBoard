<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticleUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('article.brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('article.model')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vin')
                    ->label('VIN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('engine_number')
                    ->label('Motor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plate')
                    ->label('Placa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label('Color')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'available' => 'Disponible',
                        'reserved'  => 'Reservado',
                        'sold'      => 'Vendido',
                        default     => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'available' => 'success',
                        'reserved'  => 'warning',
                        'sold'      => 'danger',
                        default     => 'gray',
                    }),
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Articulos Unitarios eliminados')
                                ->body('Los articulos unitarios seleccionados fueron eliminados correctamente.')
                        ),
                ]),
            ]);
    }
}
