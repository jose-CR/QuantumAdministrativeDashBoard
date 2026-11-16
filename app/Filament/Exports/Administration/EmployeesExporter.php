<?php

namespace App\Filament\Exports\Administration;

use App\Models\Employee;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Lo que viaja al Excel se lee como en la pantalla: el puesto y el estado salen
 * traducidos, no como el valor de la base.
 */
class EmployeesExporter extends Exporter
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label(__('resources.employees.fields.code')),

            ExportColumn::make('name')
                ->label(__('resources.employees.fields.name')),

            ExportColumn::make('document_type')
                ->label(__('resources.employees.fields.document_type')),

            ExportColumn::make('document_number')
                ->label(__('resources.employees.fields.document_number')),

            ExportColumn::make('job_title')
                ->label(__('resources.employees.fields.job_title'))
                ->formatStateUsing(fn (?string $state): ?string => $state
                    ? __('resources.employees.job_titles.' . $state)
                    : null),

            ExportColumn::make('phone')
                ->label(__('resources.employees.fields.phone')),

            ExportColumn::make('email')
                ->label(__('resources.employees.fields.email')),

            ExportColumn::make('address')
                ->label(__('resources.employees.fields.address')),

            ExportColumn::make('hire_date')
                ->label(__('resources.employees.fields.hire_date')),

            ExportColumn::make('termination_date')
                ->label(__('resources.employees.fields.termination_date')),

            ExportColumn::make('status')
                ->label(__('resources.employees.fields.status'))
                ->formatStateUsing(fn (?string $state): ?string => $state
                    ? __('resources.employees.statuses.' . $state)
                    : null),

            ExportColumn::make('user.name')
                ->label(__('resources.employees.fields.user')),

            ExportColumn::make('notes')
                ->label(__('resources.employees.fields.notes')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your employees export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
