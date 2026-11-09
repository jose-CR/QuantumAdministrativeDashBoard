<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Tables;

use App\Models\Article;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label(__('resources.inventary.article.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('category.name')
                    ->label(__('resources.inventary.article.category'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand')
                    ->label(__('resources.inventary.article.brand'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('model')
                    ->label(__('resources.inventary.article.model'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label(__('resources.inventary.article.year'))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('sku')
                    ->label(__('resources.inventary.article.sku'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tracking')
                    ->label(__('resources.inventary.article.tracking'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Article::TRACKING_SERIAL => __('resources.inventary.article.tracking_serial_short'),
                        Article::TRACKING_NONE => __('resources.inventary.article.tracking_none_short'),
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => $state === Article::TRACKING_SERIAL
                        ? 'info'
                        : 'gray'),

                TextColumn::make('cash_price')
                    ->label(__('resources.inventary.article.cash_price'))
                    ->money('USD')
                    ->placeholder('—')
                    ->sortable(),

                // Unidades serializadas (vehículos): cuántas hay registradas.
                TextColumn::make('units_count')
                    ->label(__('resources.inventary.article.units'))
                    ->counts('units')
                    ->alignCenter(),

                // Existencias de los artículos que se manejan por cantidad.
                TextColumn::make('stock')
                    ->label(__('resources.inventary.article.stock'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('resources.inventary.article.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('resources.inventary.article.updated_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // La cascada se lleva sus unidades; se avisa en la confirmación
                    // y solo se frena si alguna está usada en un crédito.
                    ->modalDescription(function (Article $record): ?string {
                        $summary = $record->deletionSummary();

                        if ($summary['units'] === 0) {
                            return null;
                        }

                        return __('notifications.inventary.article.delete.cascade', [
                            'units' => $summary['units'],
                        ]);
                    })
                    ->before(function (DeleteAction $action, Article $record) {
                        $summary = $record->deletionSummary();

                        if ($summary['units_in_credits'] > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('notifications.inventary.article.blocked.title'))
                                ->body(__('notifications.inventary.article.blocked.body', [
                                    'name' => $record->full_name,
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
                            $bloqueados = $action->getSelectedRecords()
                                ->filter(fn (Article $article) => ! $article->canBeDeletedWithChildren());

                            if ($bloqueados->isNotEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('notifications.inventary.article.blocked.title'))
                                    ->body(__('notifications.inventary.article.blocked.bulk', [
                                        'names' => $bloqueados->pluck('name')->implode(', '),
                                    ]))
                                    ->send();

                                $action->cancel();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('notifications.inventary.article.delete.title'))
                                ->body(__('notifications.inventary.article.delete.body'))
                        ),
                ]),
            ]);
    }
}
