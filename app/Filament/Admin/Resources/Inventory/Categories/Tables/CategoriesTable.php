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
                    // str_replace(null) revienta en PHP 8.4: la columna es nulable.
                    ->formatStateUsing(fn (?string $state): ?string => $state === null
                        ? null
                        : trim(str_replace(['<p>', '</p>'], '', $state)))
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Borrar una categoría con artículos viola la FK y salía como
                    // error 500. Aquí se avisa y se cancela la acción.
                    ->before(function (DeleteAction $action, $record) {
                        if ($record->articles()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title(__('notifications.inventary.category.blocked.title'))
                                ->body(__('notifications.inventary.category.blocked.body', [
                                    'name' => $record->name,
                                ]))
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action) {
                            $bloqueadas = $action->getSelectedRecords()
                                ->filter(fn ($category) => $category->articles()->exists());

                            if ($bloqueadas->isNotEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('notifications.inventary.category.blocked.title'))
                                    ->body(__('notifications.inventary.category.blocked.bulk', [
                                        'names' => $bloqueadas->pluck('name')->implode(', '),
                                    ]))
                                    ->send();

                                $action->cancel();
                            }
                        })
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
