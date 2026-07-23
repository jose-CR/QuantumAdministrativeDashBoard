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
                    ->label(__('resources.inventary.article_units.brand'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('article.model')
                    ->label(__('resources.inventary.article_units.model'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vin')
                    ->label(__('resources.inventary.article_units.vin'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('engine_number')
                    ->label(__('resources.inventary.article_units.engine_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plate')
                    ->label(__('resources.inventary.article_units.plate'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label(__('resources.inventary.article_units.color'))
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'blue'     => 'info',
                        default     => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('resources.inventary.article_units.status'))
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
                                ->title(__('notifications.inventary.articleUnits.delete.title'))
                                ->body(__('notifications.inventary.articleUnits.delete.body'))
                        ),
                ]),
            ]);
    }
}
