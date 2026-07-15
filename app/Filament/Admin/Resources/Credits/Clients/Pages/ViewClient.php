<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Pages;

use App\Filament\Admin\Resources\Credits\Clients\ClientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //EditAction::make(),
        ];
    }
}
