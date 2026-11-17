<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Pages;

use App\Filament\Admin\Resources\Credits\PaymentHistories\PaymentHistoriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Editar un pago corregido.
 *
 * El monto se puede cambiar y el modelo recalcula las cuotas y el saldo, pero además
 * queda anotado de cuánto a cuánto se corrigió y quién lo hizo: un historial que
 * cambia de número sin rastro no se puede auditar.
 */
class EditPaymentHistories extends EditRecord
{
    protected static string $resource = PaymentHistoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $montoAnterior = (float) $record->amount;

        $record = parent::handleRecordUpdate($record, $data);

        if (abs($montoAnterior - (float) $record->amount) < 0.004) {
            return $record;
        }

        $nota = __('ui.payments.corrected_note', [
            'from' => number_format($montoAnterior, 2),
            'to' => number_format((float) $record->amount, 2),
            'user' => auth()->user()?->name ?? '—',
            'date' => now()->format('d/m/Y H:i'),
        ]);

        // El editor enriquecido deja '<p></p>' cuando está vacío: no se arrastra.
        $previo = trim((string) $record->notes);
        $vacio = $previo === '' || $previo === '<p></p>';

        $record->update([
            'notes' => $vacio ? $nota : $previo . '<br>' . $nota,
        ]);

        return $record;
    }
}
