<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Pages;

use App\Filament\Admin\Resources\Inventory\Categories\CategoriesResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCategories extends CreateRecord
{
    protected static string $resource = CategoriesResource::class;

        protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Categoria creada')
            ->body('La categoria ha sido creada correctamente.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
