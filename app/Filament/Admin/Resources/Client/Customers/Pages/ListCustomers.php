<?php

namespace App\Filament\Admin\Resources\Client\Customers\Pages;

use App\Filament\Admin\Actions\CashReportAction;
use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            CashReportAction::make(),
            // Los Excel de este módulo viven en Importar y exportar.
            Action::make('datos')
                ->label(__('models.others.data_transfer'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(DataTransfer::getUrl(['model' => 'customers'])),
        ];
    }
}
