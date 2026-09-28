<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Filament\Concerns\HasAttachmentsSync;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInflow extends EditRecord
{
    use HasAttachmentsSync;

    protected static string $resource = InflowResource::class;

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
