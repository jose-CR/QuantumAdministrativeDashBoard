<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInflows extends ListRecords
{
    protected static string $resource = InflowResource::class;

    protected string $view = 'filament.admin.pages.cash-flow';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
