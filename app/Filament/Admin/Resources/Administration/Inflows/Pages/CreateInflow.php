<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Filament\Concerns\HasAttachmentsSync;
use Filament\Resources\Pages\CreateRecord;

class CreateInflow extends CreateRecord
{
    use HasAttachmentsSync;

    protected static string $resource = InflowResource::class;

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
