<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery\Pages;

use App\Filament\Admin\Resources\Inventory\Gallery\GalleryImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGallery extends ManageRecords
{
    protected static string $resource = GalleryImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('ui.images.upload')),
        ];
    }
}
