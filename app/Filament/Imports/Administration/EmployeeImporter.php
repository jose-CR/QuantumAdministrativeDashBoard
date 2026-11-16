<?php

namespace App\Filament\Imports\Administration;

use App\Models\Employee;
use App\Rules\Administration\EmployeeRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

/**
 * Acepta el archivo tal como lo escribe el export: el puesto y el estado vienen
 * traducidos ("Técnico", "De baja") y se vuelven a su valor antes de guardar.
 */
class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->rules(EmployeeRules::import()['code'])
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),

            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(EmployeeRules::import()['name'])
                ->castStateUsing(fn (?string $state): string => trim((string) $state)),

            ImportColumn::make('document_type')
                ->rules(EmployeeRules::import()['document_type'])
                ->castStateUsing(fn (?string $state): ?string => self::documentType($state)),

            ImportColumn::make('document_number')
                ->rules(EmployeeRules::import()['document_number'])
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),

            ImportColumn::make('job_title')
                ->rules(EmployeeRules::import()['job_title'])
                ->castStateUsing(fn (?string $state): ?string => self::fromLabels($state, 'job_titles', Employee::JOB_TITLES)),

            ImportColumn::make('phone')
                ->rules(EmployeeRules::import()['phone']),

            ImportColumn::make('email')
                ->rules(EmployeeRules::import()['email']),

            ImportColumn::make('address')
                ->rules(EmployeeRules::import()['address']),

            ImportColumn::make('hire_date')
                ->rules(EmployeeRules::import()['hire_date']),

            ImportColumn::make('termination_date')
                ->rules(EmployeeRules::import()['termination_date']),

            ImportColumn::make('status')
                ->rules(EmployeeRules::import()['status'])
                ->castStateUsing(fn (?string $state): ?string => self::fromLabels($state, 'statuses', Employee::STATUSES)),

            ImportColumn::make('notes')
                ->rules(EmployeeRules::import()['notes']),
        ];
    }

    /**
     * El empleado se reconoce por su documento; si no lo trae, por el nombre. Así el
     * mismo archivo sirve para altas y para actualizar los que ya están.
     */
    public function resolveRecord(): ?Employee
    {
        $documento = trim((string) ($this->data['document_number'] ?? ''));
        $tipo = $this->data['document_type'] ?? null;

        if ($documento !== '' && filled($tipo)) {
            return Employee::firstOrNew([
                'document_type' => $tipo,
                'document_number' => $documento,
            ]);
        }

        return Employee::firstOrNew([
            'name' => trim((string) $this->data['name']),
        ]);
    }

    /**
     * Acepta el valor de la base o la etiqueta traducida.
     */
    protected static function fromLabels(?string $state, string $group, array $values): ?string
    {
        $state = trim((string) $state);

        if ($state === '') {
            return null;
        }

        foreach ($values as $value) {
            if (mb_strtolower($value) === mb_strtolower($state)) {
                return $value;
            }

            if (mb_strtolower(__('resources.employees.' . $group . '.' . $value)) === mb_strtolower($state)) {
                return $value;
            }
        }

        return $state;
    }

    protected static function documentType(?string $state): ?string
    {
        $state = trim((string) $state);

        if ($state === '') {
            return null;
        }

        foreach (Employee::DOCUMENT_TYPES as $type) {
            $key = 'resources.employees.document_types.' . strtolower(str_replace(' ', '_', $type));

            if (mb_strtolower($type) === mb_strtolower($state) || mb_strtolower(__($key)) === mb_strtolower($state)) {
                return $type;
            }
        }

        return $state;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your employee import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
