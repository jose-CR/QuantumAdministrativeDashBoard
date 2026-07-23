<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Pages;

use App\Filament\Admin\Resources\Credits\Clients\ClientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('notifications.clients.delete.title'))
                        ->body(__('notifications.clients.delete.body'))
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('notifications.clients.update.title'))
            ->body(__('notifications.clients.update.body'))
        ;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
