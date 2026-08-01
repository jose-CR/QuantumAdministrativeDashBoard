<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.inventary.category.id')),


                TextColumn::make('name')
                    ->label(__('resources.inventary.category.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('resources.inventary.category.description'))
                    ->formatStateUsing(fn (?string $state) => str_replace(
                        ['<p>', '</p>'],
                        '',
                        $state
                    ))
                //
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
                                ->title(__('notifications.inventory.category.delete.title'))
                                ->body(__('notifications.inventory.category.delete.body'))
                        ),
                ]),
            ]);
    }
}
