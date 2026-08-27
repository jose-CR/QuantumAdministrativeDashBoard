<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Filament\Facades\Filament;
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
        app(InstallmentGeneratorService::class)->generate(
            credit: $this->record,
        );
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'customer_id' => request('customer'),
        ]);
    }
}
