<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
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

        $this->record->update([
        'pending_balance' => round((float) $this->record->installments()->sum('remaining_balance'), 2),
        ]);
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'customer_id' => request('customer'),
            'status' => 'active',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return CustomerResource::getUrl('view', [
            'record' => $this->record->customer_id,
        ]);
    }
}
