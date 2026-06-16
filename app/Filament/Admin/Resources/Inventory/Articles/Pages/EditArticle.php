<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Pages;

use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Articulo eliminado')
                        ->body('El articulo ha sido eliminado correctamente.')
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Articulo editado')
            ->body('El Articulo ha sido editado correctamente.')
        ;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
