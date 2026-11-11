<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('article_id')
                    ->label(__('resources.inventory.article_units.article'))
                    ->toggleable(isToggledHiddenByDefault: true),

                // El nombre del artículo ya incluye marca y modelo (o el nombre
                // propio de un artículo genérico). Antes se mostraban dos columnas
                // que quedaban vacías para todo lo que no fuera vehículo.
                TextColumn::make('article.name')
                    ->label(__('resources.inventory.article_units.article'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('cash_price')
                    ->label(__('resources.inventory.article_units.cash_price'))
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('vin')
                    ->label(__('resources.inventory.article_units.vin'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('engine_number')
                    ->label(__('resources.inventory.article_units.engine_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plate')
                    ->label(__('resources.inventory.article_units.plate'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label(__('resources.inventory.article_units.color'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    // El color es nulable: con el tipo `string` a secas, una unidad
                    // sin color tumbaba el listado entero (TypeError).
                    ->color(fn (?string $state): string => match ($state) {
                        'azul' => 'info',
                        'rojo', 'roja' => 'danger',
                        'verde' => 'success',
                        'amarillo' => 'warning',
                        'naranja' => 'orange',
                        'purpura' => 'purple',
                        'rosa' => 'pink',
                        'gris' => 'gray',
                        'negro', 'negra' => 'zinc',
                        'blanco', 'blanca' => 'slate',
                        'cafe' => 'stone',
                        'naranja con gris' => 'amber',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('resources.inventory.article_units.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'available' => 'Disponible',
                        'reserved' => 'Reservado',
                        'sold' => 'Vendido',
                        'rented' => 'Rentado',
                        'returned' => 'Regresado',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'sold' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('resources.inventory.article_units.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('resources.inventory.article_units.updated_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('notifications.inventory.articleUnits.delete.title'))
                                ->body(__('notifications.inventory.articleUnits.delete.body'))
                        ),
                ]),
            ]);
    }
}
