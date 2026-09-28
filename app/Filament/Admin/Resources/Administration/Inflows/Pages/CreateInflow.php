<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Filament\Concerns\HasAttachmentsSync;
use Filament\Resources\Pages\CreateRecord;

class CreateInflow extends CreateRecord
{
    use HasAttachmentsSync;

    protected static string $resource = InflowResource::class;

    protected function afterCreate(): void
    {
        $this->syncAttachments();
    }
}
