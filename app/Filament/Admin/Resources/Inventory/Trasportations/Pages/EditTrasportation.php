<?php

namespace App\Filament\Admin\Resources\Inventory\Trasportations\Pages;

use App\Filament\Admin\Resources\Inventory\Trasportations\TrasportationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrasportation extends EditRecord
{
    protected static string $resource = TrasportationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
