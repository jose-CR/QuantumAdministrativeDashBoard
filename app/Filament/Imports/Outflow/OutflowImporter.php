<?php

namespace App\Filament\Imports\Outflow;

use App\Models\Outflow;
use App\Rules\Flow\OutflowRules;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Illuminate\Support\Number;

class OutflowImporter extends Importer
{
    protected static ?string $model = Outflow::class;

    public static function getColumns(): array
    {
        $rules = OutflowRules::import();

        return [
            ImportColumn::make('invoice_date')
                ->label('Fecha de factura')
                ->rules($rules['invoice_date']),

            ImportColumn::make('company')
                ->label('Empresa')
                ->rules($rules['company']),

            ImportColumn::make('invoice_code')
                ->label('Código de factura')
                ->rules($rules['invoice_code']),

            ImportColumn::make('quantity')
                ->label('Cantidad')
                ->rules($rules['quantity']),

            ImportColumn::make('amount')
                ->label('Monto')
                ->rules($rules['amount']),

            ImportColumn::make('description')
                ->label('Descripción')
                ->rules($rules['description']),

            ImportColumn::make('source')
                ->label('Origen')
                ->rules($rules['source']),

            ImportColumn::make('area')
                ->label('Área')
                ->rules($rules['area']),
        ];
    }

    public function resolveRecord(): Outflow
    {
        return Outflow::firstOrNew([
            'invoice_code' => $this->data['invoice_code'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your outflow import has completed and '
            . Number::format($import->successful_rows)
            . ' '
            . str('row')->plural($import->successful_rows)
            . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                . Number::format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to import.';
        }

        return $body;
    }
}