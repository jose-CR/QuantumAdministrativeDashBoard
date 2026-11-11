<?php

namespace App\Filament\Admin\Resources\Inventory\Categories\Pages;

use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Inventory\Categories\CategoriesResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Los Excel de este módulo viven en Importar y exportar.
            Action::make('datos')
                ->label(__('models.others.data_transfer'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(DataTransfer::getUrl(['modelo' => 'categories'])),
        ];
    }
}
