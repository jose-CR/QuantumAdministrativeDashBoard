<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Pages;

use App\Filament\Admin\Resources\Credits\Installments\InstallmentsResource;
use App\Services\InstallmentGeneratorService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstallments extends EditRecord
{
    protected static string $resource = InstallmentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterCreate(): void
{
    app(InstallmentGeneratorService::class)
        ->generate($this->record);
}
}
