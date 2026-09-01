<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\ArticleUnitResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleUnit extends CreateRecord
{
    protected static string $resource = ArticleUnitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $select_price = $data['selected_price'];

        if ($select_price !== 'new') {
            $data['cash_price'] = $select_price;
        }

        unset($data['selected_price']);

        return $data;
    }

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
