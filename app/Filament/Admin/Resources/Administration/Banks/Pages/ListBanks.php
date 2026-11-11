<?php

namespace App\Filament\Admin\Resources\Administration\Banks\Pages;

use App\Filament\Admin\pages\DataTransfer;
use App\Filament\Admin\Resources\Administration\Banks\BankResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBanks extends ListRecords
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Los Excel de este módulo viven en Importar y exportar.
            Action::make('datos')
                ->label(__('models.others.data_transfer'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(DataTransfer::getUrl(['modelo' => 'banks'])),
        ];
    }
}
