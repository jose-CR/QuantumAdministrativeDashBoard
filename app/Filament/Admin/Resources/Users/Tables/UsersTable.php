<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                            ->label(__('resources.users.name')),
                TextColumn::make('email')
                            ->label(__('resources.users.email')),
                TextColumn::make('last_seen')
                            ->label(__('resources.users.last_seen')),
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
                                ->title(__('notifications.users.delete.title'))
                                ->body(__('notifications.users.delete.body'))
                        ),
                ]),
            ])
        ;
    }
}
