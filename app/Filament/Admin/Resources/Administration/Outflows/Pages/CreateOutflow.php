<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Pages;

use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use App\Filament\Concerns\HasAttachmentsSync;
use Filament\Resources\Pages\CreateRecord;

class CreateOutflow extends CreateRecord
{
    use HasAttachmentsSync;

    protected static string $resource = OutflowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->captureAttachments($data);
    }

    protected function afterCreate(): void
    {
        $this->syncAttachments();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
