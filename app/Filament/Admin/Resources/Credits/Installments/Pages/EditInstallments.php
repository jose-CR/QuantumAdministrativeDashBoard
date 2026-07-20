<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Pages;

use App\Filament\Admin\Resources\Credits\Installments\InstallmentsResource;
use App\Services\RegisterPaymentService;
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

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // Si no se ingresó un pago, no hacemos nada.
        if (empty($data['paid_amount'])) {
            return;
        }

        $installment = $this->record;
        $credit = $installment->credit;

        app(RegisterPaymentService::class)->execute(
            credit: $credit,
            amount: (float) $data['paid_amount'],
            paymentMethod: $data['payment_method'],
            receiptNumber: $data['receipt_number'] ?? null,
            notes: null,
            mode: 'single',
            installmentId: $installment->id,
            bankId: $data['bank_id'] ?? null,
            paymentDate: $data['payment_date'] ?? now(),
        );
    }
}
