<?php

namespace App\Rules\Credits;

/**
 * Reglas de la importación de créditos.
 *
 * Antes pedía `client_id`, `article_unit_id` y `assigned_user_id`: ninguna de esas
 * columnas existe en `credits`, así que el import descartaba el cliente y el bien en
 * silencio. Ahora valida las columnas reales de la tabla.
 */
class LoanRules
{
    public const PERIODICITIES = ['weekly', 'monthly', 'yearly'];

    public const INTEREST_TYPES = ['auto', 'fixed'];

    public static function import(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'refinanced_from_id' => ['nullable', 'integer', 'exists:credits,id'],
            'initial_amount' => ['required', 'numeric', 'min:0.01'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'financed_amount' => ['required', 'numeric', 'min:0'],
            'installments' => ['required', 'integer', 'min:1'],
            'installment_amount' => ['required', 'numeric', 'min:0.01'],
            'periodicity' => ['required', 'in:' . implode(',', self::PERIODICITIES)],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interest_type' => ['nullable', 'in:' . implode(',', self::INTEREST_TYPES)],
            'total_interest' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'pending_balance' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'payment_day' => ['required', 'integer', 'between:1,31'],
            'payment_month' => ['nullable', 'integer', 'between:1,12'],
            'status' => ['required', 'in:' . implode(',', config('quantum.statuses.credit'))],

            // El bien que se financia: la serie del vehículo. Si viene y existe,
            // el import crea el ítem del crédito.
            'vin' => ['nullable', 'string', 'exists:article_units,vin'],
        ];
    }
}
