<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Pages;

use App\Filament\Admin\Resources\Credits\Installments\InstallmentsResource;
use App\Filament\Exports\Credits\InstallmentsExporter;
use App\Filament\Imports\Credits\InstallmentImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallments extends ListRecords
{
    protected static string $resource = InstallmentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
            ExportAction::make()
                ->exporter(InstallmentsExporter::class),
            ImportAction::make()
                ->importer(InstallmentImporter::class)
        ];
    }
}
