<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Pages;

use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use App\Filament\Concerns\HasAttachmentsSync;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOutflow extends EditRecord
{
    use HasAttachmentsSync;

    protected static string $resource = OutflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->captureAttachments($data);
    }

    protected function afterSave(): void
    {
        $this->syncAttachments();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
