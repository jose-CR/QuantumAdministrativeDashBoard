<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\ArticleUnitResource;
use App\Filament\Exports\Inventory\UnitArticlesExporter;
use App\Filament\Imports\Inventory\ArticleImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListArticleUnits extends ListRecords
{
    protected static string $resource = ArticleUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->exporter(UnitArticlesExporter::class),
            ImportAction::make()
                ->importer(ArticleImporter::class),
        ];
    }
}
