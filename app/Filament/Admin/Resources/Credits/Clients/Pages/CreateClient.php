<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Pages;

use App\Filament\Admin\Resources\Credits\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cliente creado')
            ->body('El Cliente ha sido creado correctamente.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
