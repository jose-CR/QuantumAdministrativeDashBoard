<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Pages;

use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use App\Filament\Exports\Inventory\ArticlesExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
            ->exporter(ArticlesExporter::class)
        ];
    }
}
