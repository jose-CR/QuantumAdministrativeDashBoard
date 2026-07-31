<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Filament\Exports\Credits\LoansExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoansResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
            ->exporter(LoansExporter::class),
        ];
    }
}
