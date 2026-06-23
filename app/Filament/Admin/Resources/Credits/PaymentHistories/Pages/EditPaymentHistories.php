<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Pages;

use App\Filament\Admin\Resources\Credits\PaymentHistories\PaymentHistoriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentHistories extends EditRecord
{
    protected static string $resource = PaymentHistoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
