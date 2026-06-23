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
        return app(RegisterPaymentService::class)->execute(
            Credit::findOrFail(
                $data['credit_id']
            ),
            $data['amount'],
            $data['payment_method'],
            $data['receipt_number'] ?? null,
            $data['notes'] ?? null,
        );
    }

}
