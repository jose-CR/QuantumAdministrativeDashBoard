<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Filament\Exports\Inflow\InflowExporter;
use App\Filament\Imports\Inflow\InflowImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListInflows extends ListRecords
{
    protected static string $resource = InflowResource::class;

    protected string $view = 'filament.admin.pages.cash-flow';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->exporter(InflowExporter::class),
            ImportAction::make()
                ->importer(InflowImporter::class),
        ];
    }
}
