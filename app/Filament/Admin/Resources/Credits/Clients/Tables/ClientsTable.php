<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identity_document')
                    ->label('DUI'),
                TextColumn::make('phone_primary')
                    ->label('Telefonos')
                    ->formatStateUsing(function ($state, $record) {
                        return $state . ' / ' . $record->phone_secondary;
                    }),
                TextColumn::make('address')
                    ->label('direccion'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Clientes eliminados')
                                ->body('Los Clientes seleccionados fueron eliminados correctamente.')
                        ),
                ]),
            ]);
    }
}
