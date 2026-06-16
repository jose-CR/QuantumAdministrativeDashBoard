<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Tables;

use Filament\Actions\BulkActionGroup;
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
                TextColumn::make('name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('descripcion')
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
                                ->title('Categorias eliminados')
                                ->body('Las categorias seleccionados fueron eliminados correctamente.')
                        ),
                ]),
            ]);
    }
}
