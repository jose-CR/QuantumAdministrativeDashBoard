<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Services\Credits\InstallmentSyncService;
use App\Services\LoanCalculatorService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EditLoans extends EditRecord
{
    protected static string $resource = LoansResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = array_merge(
            $data,
            app(LoanCalculatorService::class)->calculate($data)
        );

        // El saldo se calcula después de sincronizar las cuotas.
        unset($data['pending_balance']);

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        // Capturar los valores originales antes de guardar.
        $oldInstallmentAmount = (float) $record->installment_amount;
        $oldInstallments = (int) $record->installments;

        try {
            return DB::transaction(function () use (
                $record,
                $data,
                $oldInstallmentAmount,
                $oldInstallments,
            ): Model {
                $record = parent::handleRecordUpdate($record, $data);

                app(InstallmentSyncService::class)->sync(
                    $record,
                    $oldInstallmentAmount,
                    $oldInstallments,
                );

                return $record;
            });
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();

            throw new Halt();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return CustomerResource::getUrl('view', [
            'record' => $this->record->customer_id,
        ]);
    }
}
