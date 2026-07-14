<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\ArticleUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditArticleUnit extends EditRecord
{
    protected static string $resource = ArticleUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('notifications.inventary.articleUnits.delete.title'))
                        ->body(__('notifications.inventary.articleUnits.delete.body'))
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('notifications.inventary.articleUnits.update.title'))
            ->body(__('notifications.inventary.articleUnits.update.body'))
        ;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
