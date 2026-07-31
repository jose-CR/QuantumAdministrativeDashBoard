<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Pages;

use App\Filament\Admin\Resources\Credits\PaymentHistories\PaymentHistoriesResource;
use App\Filament\Exports\Credits\PaymentHistoriesExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentHistories extends ListRecords
{
    protected static string $resource = PaymentHistoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
            ExportAction::make()
                ->exporter(PaymentHistoriesExporter::class)
        ];
    }
}
