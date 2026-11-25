<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Exceptions\BusinessRuleException;
use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use App\Support\Ui\Alert;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLoans extends CreateRecord
{
    protected static string $resource = LoansResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $calculado = app(LoanCalculatorService::class)->calculate($data);
        } catch (BusinessRuleException $error) {
            // Sin esto, un número mal puesto (cuotas en cero, prima mayor al monto)
            // salía como pantalla de error en vez de marcar el campo.
            Alert::fromRule($error, __('ui.errors.save_credit'));
        }

        return array_merge($data, $calculado);
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
