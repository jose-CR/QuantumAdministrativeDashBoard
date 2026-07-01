<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Pages;

use App\Filament\Admin\Resources\Credits\Clients\ClientResource;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function afterCreate(): void
    {
        $client = $this->record;

        $creditData = [
            'article_unit_id'      => $this->data['article_unit_id'],
            'refinanced_from_id'   => $this->data['refinanced_from_id'],
            'initial_amount'       => $this->data['initial_amount'],
            'down_payment'         => $this->data['down_payment'],
            'installments'         => $this->data['installments'],
            'installment_amount'   => $this->data['installment_amount'],
            'periodicity'          => $this->data['periodicity'],
            'start_date'           => $this->data['start_date'],
            'payment_day'          => $this->data['payment_day'],
            'status'               => $this->data['status'],
        ];

        $creditData = array_merge(
            $creditData,
            app(LoanCalculatorService::class)
                ->calculate($creditData)
        );

        $credit = $client->credits()->create($creditData);

        app(InstallmentGeneratorService::class)->generate($credit);
    }



    /*     protected function afterCreate(): void
        {
            app(
                InstallmentGeneratorService::class
            )->generate(
                $this->record
            );
        } */

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cliente creado')
            ->body('El Cliente ha sido creado correctamente.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
