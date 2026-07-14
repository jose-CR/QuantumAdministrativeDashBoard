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
                    ->label(__('resources.inventary.article.brand'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->label(__('resources.inventary.article.model'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                     ->label(__('resources.inventary.article.color'))
                    ->searchable()
                    ->color(fn (string $state) => match ($state) {
                        'Rojo'     => 'danger',
                        'Verde'    => 'success',
                        'Azul'     => 'info',
                        'Amarillo' => 'warning',
                        'Gris'     => 'gray',
                        'Negro'    => 'gray',
                        'Blanco'   => 'gray',
                        default    => 'primary',
                    }),

                TextColumn::make('cash_price')
                    ->label(__('resources.inventary.article.cash_price'))
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('resources.inventary.article.description'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->descripcion),

                TextColumn::make('created_at')
                    ->label(__('resources.inventary.article.created_at'))
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
                                ->title(__('notifications.inventary.article.delete.title'))
                                ->body(__('notifications.inventary.articleUnits.delete.body'))
                        ),
                ]),
            ]);
    }
}
