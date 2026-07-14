<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\ArticleUnitResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleUnit extends CreateRecord
{
    protected static string $resource = ArticleUnitResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('notifications.inventary.articleUnits.create.title'))
            ->body(__('notifications.inventary.articleUnits.create.body'));
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
