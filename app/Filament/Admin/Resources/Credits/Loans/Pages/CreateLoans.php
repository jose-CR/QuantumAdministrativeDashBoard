<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Filament\Resources\Pages\CreateRecord;

class CreateLoans extends CreateRecord
{
    protected static string $resource = LoansResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $calculator = app(LoanCalculatorService::class);

        return array_merge(
            $data,
            $calculator->calculate($data)
        );
    }



    protected function afterCreate(): void
    {
        app(
            InstallmentGeneratorService::class
        )->generate(
            $this->record
        );
    }
}
