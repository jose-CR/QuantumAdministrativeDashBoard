<?php

namespace App\Filament\Admin\Resources\Client\Customers\Pages;

use App\Filament\Admin\Actions\AttachFileAction;
use App\Filament\Admin\Actions\PayInstallmentAction;
use App\Filament\Admin\Actions\RefinanceCreditAction;
use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            PayInstallmentAction::make(),
            RefinanceCreditAction::make(),
            // Subir la prima o cualquier comprobante con el FileUpload de Filament.
            AttachFileAction::make(),
            Action::make('createCredit')
                ->url(fn (Customer $record): string => route(
                    'filament.admin.resources.creditos.loans.create',
                    [
                        'customer' => $record->id,
                    ],
                )),
        ];
    }
}
