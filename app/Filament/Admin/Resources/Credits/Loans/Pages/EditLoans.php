<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Pages;

use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditLoans extends EditRecord
{
    protected static string $resource = LoansResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {

        $calculator = app(LoanCalculatorService::class);

        return array_merge(
            $data,
            $calculator->calculate($data)
        );
    }

    protected function afterSave(): void
    {

        $creator = Filament::auth()->user();

        $assignedUser = null;

        if (! empty($data['assigned_user_id'])) {
            $assignedUser = User::findOrFail(
                $data['assigned_user_id']
            );
        }

        app(
            InstallmentGeneratorService::class
        )->generate(
                credit: $this->record,
                creator: $creator,
                assignedUser: $assignedUser,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
