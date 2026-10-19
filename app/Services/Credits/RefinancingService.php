<?php

namespace App\Services\Credits;

use App\Models\Credit;
use App\Models\Customer;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Exception;
use Illuminate\Support\Facades\DB;

class RefinancingService
{
    public function execute(Customer $client, Credit $oldCredit, array $data): Credit
    {
        return DB::transaction(function () use ($client, $oldCredit, $data) {
            $oldCredit = Credit::lockForUpdate()->findOrFail($oldCredit->id);

            if ($oldCredit->status !== 'active') {
                throw new Exception('Solo se pueden refinanciar créditos activos.');
            }

            $data = array_merge($data, [
                'initial_amount' => $oldCredit->pending_balance,
                'down_payment' => $oldCredit->down_payment,
            ]);

            $newCredit = $client->credits()->create(array_merge(
                $data,
                [
                    'status' => 'active',
                    'refinanced_from_id' => $oldCredit->id,
                ],
                app(LoanCalculatorService::class)->calculate($data),
            ));

            $oldCredit->items->each(fn ($item) => $newCredit->items()->save($item->replicate()));

            app(InstallmentGeneratorService::class)->generate($newCredit);

            $oldCredit->update([
                'status' => 'refinanced',
                'pending_balance' => 0,
            ]);

            $oldCredit->installments()
                ->whereIn('status', ['pending', 'late'])
                ->update(['status' => 'refinanced']);

            return $newCredit;
        });
    }
}