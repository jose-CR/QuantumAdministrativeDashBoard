<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Tables;

use App\Models\Category;
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
                    // La cascada se lleva los artículos y sus unidades; se avisa
                    // en la confirmación y solo se frena si alguna unidad está en
                    // un crédito (esos ítems no tienen FK y quedarían huérfanos).
                    ->modalDescription(function (Category $record): ?string {
                        $summary = $record->deletionSummary();

                        if ($summary['articles'] === 0) {
                            return null;
                        }

                        return __('notifications.inventary.category.delete.cascade', [
                            'articles' => $summary['articles'],
                            'units' => $summary['units'],
                        ]);
                    })
                    ->before(function (DeleteAction $action, Category $record) {
                        $summary = $record->deletionSummary();

                        if ($summary['units_in_credits'] > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('notifications.inventary.category.blocked.title'))
                                ->body(__('notifications.inventary.category.blocked.body', [
                                    'name' => $record->name,
                                    'units' => $summary['units_in_credits'],
                                    'credits' => $record->creditsBlockingDeletionLabel(),
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
                                ->filter(fn (Category $category) => ! $category->canBeDeletedWithChildren());

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
