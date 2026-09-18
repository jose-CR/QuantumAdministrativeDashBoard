<?php

namespace App\Filament\Imports\Inflow;

use App\Models\Inflow;
use App\Rules\Flow\InflowRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class InflowImporter extends Importer
{
    protected static ?string $model = Inflow::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('invoice_number')
                ->label('Número de factura')
                ->rules(InflowRules::import()['invoice_number']),

            ImportColumn::make('date')
                ->label('Fecha')
                ->rules(InflowRules::import()['date']),

            ImportColumn::make('description')
                ->label('Descripción')
                ->rules(InflowRules::import()['description']),

            ImportColumn::make('customer_id')
                ->label('Cliente')
                ->rules(InflowRules::import()['customer_id']),

            ImportColumn::make('amount')
                ->label('Monto')
                ->rules(InflowRules::import()['amount']),

            ImportColumn::make('payment_method')
                ->label('Método de pago')
                ->rules(InflowRules::import()['payment_method']),

            ImportColumn::make('transfer_number')
                ->label('Número de transferencia')
                ->rules(InflowRules::import()['transfer_number']),

            ImportColumn::make('bank_id')
                ->label('Banco')
                ->rules(InflowRules::import()['bank_id']),

            ImportColumn::make('transfer_date')
                ->label('Fecha de transferencia')
                ->rules(InflowRules::import()['transfer_date']),

            ImportColumn::make('payment_status')
                ->label('Estado de pago')
                ->rules(InflowRules::import()['payment_status']),

            ImportColumn::make('salesperson')
                ->label('Vendedor')
                ->rules(InflowRules::import()['salesperson']),

            ImportColumn::make('notes')
                ->label('Notas')
                ->rules(InflowRules::import()['notes']),
        ];
    }

    public function resolveRecord(): Inflow
    {
        return Inflow::firstOrNew([
            'invoice_number' => $this->data['invoice_number'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your inflow import has completed and '
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