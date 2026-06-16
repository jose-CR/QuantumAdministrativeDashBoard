<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Pages;

use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Articulo creado')
            ->body('El articulo ha sido creado correctamente.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
