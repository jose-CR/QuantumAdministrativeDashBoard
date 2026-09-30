<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Pages;

use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutflows extends ListRecords
{
    protected static string $resource = OutflowResource::class;

    protected string $view = 'filament.admin.pages.cash-flow';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

