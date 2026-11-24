<?php

namespace App\Filament\Admin\Resources\Settings\DocumentTypes\Pages;

use App\Filament\Admin\Resources\Settings\DocumentTypes\DocumentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDocumentTypes extends ManageRecords
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
