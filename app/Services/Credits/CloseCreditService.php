<?php

namespace App\Services\Credits;

use App\Models\Credit;
use App\Models\Installment;

class CloseCreditService
{
    public function execute(Credit $credit): void
    {
        $credit->refresh();

        // Cualquier cuota impaga mantiene el crédito abierto. Antes solo miraba
        // 'pending': una cuota 'late' con saldo dejaba cerrar el crédito con deuda.
        if (
            $credit->installments()
                ->whereIn('status', Installment::UNPAID_STATUSES)
                ->exists()
        ) {
            return;
        }

        $credit->update([
            'status' => 'completed',
            'pending_balance' => 0,
        ]);
    }
}
