<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Pages;

use App\Filament\Admin\Resources\Credits\PaymentHistories\PaymentHistoriesResource;
use App\Models\Credit;
use App\Services\RegisterPaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePaymentHistories extends CreateRecord
{
    protected static string $resource = PaymentHistoriesResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Se pasan todos los datos del formulario: antes se descartaban la fecha
        // y el banco, así que el pago quedaba con la fecha de hoy y sin banco.
        return app(RegisterPaymentService::class)->execute(
            credit: Credit::findOrFail($data['credit_id']),
            amount: (float) $data['amount'],
            paymentMethod: $data['payment_method'],
            receiptNumber: $data['receipt_number'] ?? null,
            notes: $data['notes'] ?? null,
            bankId: $data['bank_id'] ?? null,
            paymentDate: $data['payment_date'] ?? null,
        );
    }

}
