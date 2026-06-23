<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoans extends EditRecord
{
    protected static string $resource = LoansResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {

        $calculator = app(LoanCalculatorService::class);

        return array_merge(
            $data,
            $calculator->calculate($data)
        );
    }

    protected function afterSave(): void
    {
        app(
            InstallmentGeneratorService::class
        )->generate(
            $this->record
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
