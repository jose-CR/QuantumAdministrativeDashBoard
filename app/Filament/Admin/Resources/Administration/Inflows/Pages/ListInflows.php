<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Filament\Exports\Inflow\InflowExporter;
use App\Filament\Imports\Inflow\InflowImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListInflows extends ListRecords
{
    protected static string $resource = InflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make('exportReport')
                ->label('Reporte de entradas')
                ->icon('heroicon-o-chart-bar')
                ->exporter(InflowExporter::class)
                ->formats([ExportFormat::Xlsx])
                ->columnMapping(false)
                ->fileName(fn (Export $export): string => "reporte-entradas-{$export->getKey()}"),
            ExportAction::make()
                ->exporter(InflowExporter::class),
            ImportAction::make()
                ->importer(InflowImporter::class),
        ];
    }
}
