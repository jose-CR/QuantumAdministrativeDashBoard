<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Pages;

use App\Filament\Admin\Resources\Inventory\Categories\CategoriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
