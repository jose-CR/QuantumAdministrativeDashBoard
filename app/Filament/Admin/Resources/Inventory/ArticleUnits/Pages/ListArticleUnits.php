<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\ArticleUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticleUnits extends ListRecords
{
    protected static string $resource = ArticleUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
