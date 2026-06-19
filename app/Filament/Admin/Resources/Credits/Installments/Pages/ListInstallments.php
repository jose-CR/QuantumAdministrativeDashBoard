<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Pages;

use App\Filament\Admin\Resources\Credits\Installments\InstallmentsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallments extends ListRecords
{
    protected static string $resource = InstallmentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
        ];
    }
}
