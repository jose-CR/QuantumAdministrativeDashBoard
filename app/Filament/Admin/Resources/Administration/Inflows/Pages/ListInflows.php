<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Pages;

use App\Filament\Admin\Actions\CashReportAction;
use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInflows extends ListRecords
{
    protected static string $resource = InflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Caja + cartera + inventario en un solo Excel.
            CashReportAction::make(),
            // Los Excel de este módulo viven en Importar y exportar.
            Action::make('datos')
                ->label(__('models.others.data_transfer'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(DataTransfer::getUrl(['model' => 'inflows'])),
        ];
    }
}
