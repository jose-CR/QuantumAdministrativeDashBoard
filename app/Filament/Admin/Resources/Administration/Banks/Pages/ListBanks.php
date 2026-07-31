<?php

namespace App\Filament\Admin\Resources\Administration\Banks\Pages;

use App\Filament\Admin\Resources\Administration\Banks\BankResource;
use App\Filament\Exports\Administration\BanksExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListBanks extends ListRecords
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
            ->exporter(BanksExporter::class),
        ];
    }
}
