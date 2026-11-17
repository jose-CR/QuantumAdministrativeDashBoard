<?php

namespace App\Filament\Imports\Credits;

use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Rules\Credits\LoanRules;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

/**
 * Importación de créditos.
 *
 * El archivo trae las columnas reales de `credits` y, opcionalmente, el `vin` del
 * bien financiado: si viene, se crea el ítem del crédito con esa unidad.
 */
class LoanImporter extends Importer
{
    protected static ?string $model = Credit::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('customer_id')
                ->label(__('resources.credits.fields.client'))
                ->requiredMapping()
                ->integer()
                ->rules(LoanRules::import()['customer_id']),

            ImportColumn::make('refinanced_from_id')
                ->label(__('resources.credits.fields.refinanced_from'))
                ->integer()
                ->rules(LoanRules::import()['refinanced_from_id']),

            ImportColumn::make('initial_amount')
                ->label(__('resources.credits.fields.initial_amount'))
                ->requiredMapping()
                ->numeric()
                ->rules(LoanRules::import()['initial_amount']),

            ImportColumn::make('down_payment')
                ->label(__('resources.credits.fields.down_payment'))
                ->numeric()
                ->rules(LoanRules::import()['down_payment']),

            ImportColumn::make('financed_amount')
                ->label(__('resources.credits.fields.financed_amount'))
                ->requiredMapping()
                ->numeric()
                ->rules(LoanRules::import()['financed_amount']),

            ImportColumn::make('installments')
                ->label(__('resources.credits.fields.installments'))
                ->requiredMapping()
                ->integer()
                ->rules(LoanRules::import()['installments']),

            ImportColumn::make('installment_amount')
                ->label(__('resources.credits.fields.installment_amount'))
                ->requiredMapping()
                ->numeric()
                ->rules(LoanRules::import()['installment_amount']),

            ImportColumn::make('periodicity')
                ->label(__('resources.credits.fields.periodicity'))
                ->requiredMapping()
                ->rules(LoanRules::import()['periodicity']),

            ImportColumn::make('interest_rate')
                ->label(__('resources.credits.fields.interest_rate'))
                ->numeric()
                ->rules(LoanRules::import()['interest_rate']),

            ImportColumn::make('interest_type')
                ->label(__('resources.credits.credits.interest_type'))
                ->rules(LoanRules::import()['interest_type']),

            ImportColumn::make('total_interest')
                ->label(__('resources.credits.fields.total_interest'))
                ->numeric()
                ->rules(LoanRules::import()['total_interest']),

            ImportColumn::make('total_amount')
                ->label(__('resources.credits.fields.total_amount'))
                ->requiredMapping()
                ->numeric()
                ->rules(LoanRules::import()['total_amount']),

            ImportColumn::make('pending_balance')
                ->label(__('resources.credits.fields.pending_balance'))
                ->numeric()
                ->rules(LoanRules::import()['pending_balance']),

            ImportColumn::make('start_date')
                ->label(__('resources.credits.credits.start_date'))
                ->requiredMapping()
                ->rules(LoanRules::import()['start_date']),

            ImportColumn::make('payment_day')
                ->label(__('resources.credits.credits.payment_day'))
                ->requiredMapping()
                ->integer()
                ->rules(LoanRules::import()['payment_day']),

            ImportColumn::make('payment_month')
                ->label(__('resources.credits.credits.payment_month'))
                ->integer()
                ->rules(LoanRules::import()['payment_month']),

            ImportColumn::make('status')
                ->label(__('resources.credits.credits.status'))
                ->requiredMapping()
                ->rules(LoanRules::import()['status']),

            /*
             * El bien no es una columna del crédito: se importa la serie y el ítem se
             * crea después de guardar. `fillRecordUsing` evita que Eloquent intente
             * escribirla en `credits`.
             */
            ImportColumn::make('vin')
                ->label(__('resources.credits.fields.article_unit'))
                ->rules(LoanRules::import()['vin'])
                ->fillRecordUsing(fn (): null => null),
        ];
    }

    /**
     * Cada fila es un crédito nuevo: un cliente puede tener varios y no hay una clave
     * natural para actualizar uno existente.
     */
    public function resolveRecord(): Credit
    {
        return new Credit();
    }

    public function afterSave(): void
    {
        $vin = trim((string) ($this->data['vin'] ?? ''));

        if ($vin === '' || $this->record->items()->exists()) {
            return;
        }

        $unidad = ArticleUnit::query()->where('vin', $vin)->first();

        if (! $unidad) {
            return;
        }

        $this->record->items()->create([
            'item_type' => ArticleUnit::class,
            'item_id' => $unidad->id,
            'price' => $this->record->initial_amount,
            'quantity' => 1,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your loan import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
