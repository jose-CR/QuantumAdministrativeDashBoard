<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label('Color')
                    ->searchable(),

                TextColumn::make('cash_price')
                    ->label('Precio Contado')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('credit_price')
                    ->label('Precio Crédito')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->descripcion),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
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
                                ->title('Articulos eliminados')
                                ->body('Los articulos seleccionados fueron eliminados correctamente.')
                        ),
                ]),
            ]);
    }
}
