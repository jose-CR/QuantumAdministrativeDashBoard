<?php

namespace App\Services\Credits;

use App\Models\Credit;

class CloseCreditService
{

    public function execute(Credit $credit): void{
        $credit->refresh();

        if (
            $credit->installments()
                ->where('status', 'pending')
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
