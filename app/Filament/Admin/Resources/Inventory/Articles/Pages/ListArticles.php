<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Pages;

use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Los Excel de este módulo viven en Importar y exportar.
            Action::make('datos')
                ->label(__('models.others.data_transfer'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(DataTransfer::getUrl(['modelo' => 'articles'])),
        ];
    }
}
