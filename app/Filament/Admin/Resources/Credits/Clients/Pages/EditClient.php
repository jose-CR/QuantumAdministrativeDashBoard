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
                        ->title('Cliente eliminado')
                        ->body('El Ciente ha sido eliminado correctamente.')
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cliente editado')
            ->body('El Cliente ha sido editado correctamente.')
        ;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
