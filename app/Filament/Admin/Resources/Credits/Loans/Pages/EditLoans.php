<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Exceptions\BusinessRuleException;
use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Services\Credits\InstallmentSyncService;
use App\Services\LoanCalculatorService;
use App\Support\Ui\Alert;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EditLoans extends EditRecord
{
    protected static string $resource = LoansResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data): Model {
                $data = array_merge($data, app(LoanCalculatorService::class)->calculate($data));

                // El saldo se calcula después de sincronizar las cuotas.
                unset($data['pending_balance']);

                $record = parent::handleRecordUpdate($record, $data);

                app(InstallmentSyncService::class)->sync($record);

                return $record;
            });
        } catch (BusinessRuleException $e) {
            Alert::fromRule($e, __('ui.errors.save_credit'));
        } catch (InvalidArgumentException $e) {
            // Los servicios viejos todavía lanzan el mensaje suelto.
            Alert::stop($e->getMessage(), __('ui.errors.save_credit'));
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
