<?php

namespace App\Filament\Admin\Resources\Administration\Payroll\Pages;

use App\Filament\Admin\Resources\Administration\Payroll\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    /**
     * Las fechas y la etiqueta salen de año + mes + quincena, no de lo que quede en
     * el formulario.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['start_date'] ?? null) || blank($data['end_date'] ?? null)) {
            $fechas = PayrollPeriod::datesFor(
                (int) now()->year,
                (int) now()->month,
                now()->day <= 15 ? 'first' : 'second',
            );

            $data = array_merge($data, $fechas);
        }

        return $data;
    }
}
