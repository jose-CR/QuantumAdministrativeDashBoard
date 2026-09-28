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

    protected function afterSave(): void
    {
        $this->syncAttachments();
    }
}
