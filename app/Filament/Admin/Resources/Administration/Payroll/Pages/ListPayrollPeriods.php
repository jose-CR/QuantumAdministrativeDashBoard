<?php

namespace App\Filament\Admin\Resources\Administration\Payroll\Pages;

use App\Filament\Admin\Actions\PayrollSettingsAction;
use App\Filament\Admin\Resources\Administration\Payroll\PayrollPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // Los porcentajes de ley los ajusta el encargado desde acá.
            PayrollSettingsAction::make(),
        ];
    }
}
