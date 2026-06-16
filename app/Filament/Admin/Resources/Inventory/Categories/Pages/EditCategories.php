<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Pages;

use App\Filament\Admin\Resources\Inventory\Categories\CategoriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCategories extends EditRecord
{
    protected static string $resource = CategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Categoria eliminada')
                        ->body('La categoria ha sido eliminada correctamente.')
                ),
        ];
    }

        protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Categoria editada')
            ->body('La categoria ha sido editada correctamente.')
        ;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
}
