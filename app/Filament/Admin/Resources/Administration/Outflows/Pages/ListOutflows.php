<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Pages;

use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use App\Filament\Exports\Outflow\OutflowExporter;
use App\Filament\Imports\Outflow\OutflowImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListOutflows extends ListRecords
{
    protected static string $resource = OutflowResource::class;

    protected string $view = 'filament.admin.pages.cash-flow';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('exportar salidas')
                ->exporter(OutflowExporter::class),
            ImportAction::make()
                ->importer(OutflowImporter::class),
        ];
    }
}

