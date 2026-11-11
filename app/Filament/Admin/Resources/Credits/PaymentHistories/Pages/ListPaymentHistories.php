<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Pages;

use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Credits\PaymentHistories\PaymentHistoriesResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentHistories extends ListRecords
{
    protected static string $resource = PaymentHistoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Los Excel de este módulo viven en Importar y exportar.
            Action::make('datos')
                ->label(__('models.others.data_transfer'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(DataTransfer::getUrl(['modelo' => 'payments'])),
        ];
    }
}
