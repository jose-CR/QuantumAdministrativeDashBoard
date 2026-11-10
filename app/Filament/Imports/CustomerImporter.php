<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use App\Rules\CustomerRules;
use App\Support\ElSalvadorCatalogo;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('document_type')
                ->requiredMapping()
                ->rules(CustomerRules::import()['document_type']),

            ImportColumn::make('document_number')
                ->requiredMapping()
                ->rules(CustomerRules::import()['document_number']),

            ImportColumn::make('full_name')
                ->requiredMapping()
                ->rules(CustomerRules::import()['full_name']),

            ImportColumn::make('email')
                ->rules(CustomerRules::import()['email']),

            ImportColumn::make('phone_primary')
                ->rules(CustomerRules::import()['phone_primary']),

            ImportColumn::make('phone_secondary')
                ->rules(CustomerRules::import()['phone_secondary']),

            ImportColumn::make('nrc')
                ->rules(CustomerRules::import()['nrc']),

            ImportColumn::make('economic_activity')
                ->rules(CustomerRules::import()['economic_activity']),

            ImportColumn::make('department')
                ->requiredMapping()
                ->rules(CustomerRules::import()['department']),

            ImportColumn::make('municipality')
                ->requiredMapping()
                ->rules(CustomerRules::import()['municipality']),

            ImportColumn::make('district')
                ->requiredMapping()
                ->rules(CustomerRules::import()['district']),

            ImportColumn::make('address')
                ->requiredMapping()
                ->rules(CustomerRules::import()['address']),
        ];
    }

    /**
     * El export escribe nombres ("San Salvador") y la base guarda códigos ("06"):
     * se traducen antes de llenar el modelo. Si ya viene el código, no cambia nada.
     */
    public function fillRecord(): void
    {
        $departamento = ElSalvadorCatalogo::resolveDepartment($this->data['department'] ?? null);

        $municipio = ElSalvadorCatalogo::resolveMunicipality(
            $departamento,
            $this->data['municipality'] ?? null,
        );

        $distrito = ElSalvadorCatalogo::resolveDistrict(
            $municipio,
            $this->data['district'] ?? null,
        );

        $this->data['department'] = $departamento ?? ($this->data['department'] ?? null);
        $this->data['municipality'] = $municipio ?? ($this->data['municipality'] ?? null);
        $this->data['district'] = $distrito ?? ($this->data['district'] ?? null);

        parent::fillRecord();
    }

    public function resolveRecord(): Customer
    {
        // Se resuelve por documento, no por id: importar ids explícitos desfasa la
        // secuencia de Postgres y después las altas manuales fallan por id duplicado
        // (lo mismo que pasó con el inventario).
        $documento = trim((string) ($this->data['document_number'] ?? ''));

        if ($documento === '') {
            return new Customer();
        }

        return Customer::query()->firstOrNew([
            'document_type' => $this->data['document_type'] ?? null,
            'document_number' => $documento,
        ]);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('notifications.clients.import.title');
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('notifications.clients.import.body', [
            'rows' => Number::format($import->successful_rows),
        ]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . __('notifications.clients.import.failed', [
                'rows' => Number::format($failedRowsCount),
            ]);
        }

        return $body;
    }
}
