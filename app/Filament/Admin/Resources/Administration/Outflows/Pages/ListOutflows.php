<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Pages;

use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use App\Filament\Exports\Outflow\OutflowExporter;
use App\Filament\Imports\Outflow\OutflowImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListOutflows extends ListRecords
{
    protected static string $resource = OutflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make('exportReport')
                ->label('Reporte de salidas')
                ->icon('heroicon-o-chart-bar')
                ->exporter(OutflowExporter::class)
                ->formats([ExportFormat::Xlsx])
                ->columnMapping(false)
                ->fileName(fn (Export $export): string => "reporte-de-salidas-{$export->getKey()}"),
            ExportAction::make()
                ->exporter(OutflowExporter::class)
                ->columnMapping(false),
            ImportAction::make()
                ->importer(OutflowImporter::class),
        ];
    }
}

