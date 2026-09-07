<?php

namespace App\Filament\Admin\Resources\Inventory\Trasportations\Pages;

use App\Filament\Admin\Resources\Inventory\Trasportations\TrasportationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrasportations extends ListRecords
{
    protected static string $resource = TrasportationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
