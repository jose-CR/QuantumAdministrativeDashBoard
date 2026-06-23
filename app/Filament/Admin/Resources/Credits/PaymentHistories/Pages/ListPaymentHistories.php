<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Pages;

use App\Filament\Admin\Resources\Credits\PaymentHistories\PaymentHistoriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentHistories extends ListRecords
{
    protected static string $resource = PaymentHistoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
