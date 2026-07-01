<?php

namespace App\Services\Credits;

use App\Models\Client;
use App\Models\Credit;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Exception;
use Illuminate\Support\Facades\DB;

class RefinancingService
{
    public function execute(
        Client $client,
        Credit $oldCredit,
        array $data
    ): Credit {

        if ($oldCredit->status !== 'active') {
            throw new Exception(
                'Solo se pueden refinanciar créditos activos.'
            );
        }

        return DB::transaction(function () use (
            $client,
            $oldCredit,
            $data
        ) {

            $creditData = array_merge(
                $data,
                [
                    'article_unit_id' => $oldCredit->article_unit_id,
                    'status' => 'active',
                    'refinanced_from_id' => $oldCredit->id,
                ],
                app(LoanCalculatorService::class)
                    ->calculate($data)
            );

            $newCredit = $client
                ->credits()
                ->create($creditData);

            app(
                InstallmentGeneratorService::class
            )->generate($newCredit);

            $oldCredit->update([
                'status' => 'refinanced',
                'pending_balance' => 0,
            ]);

            $oldCredit
                ->installments()
                ->whereIn('status', [
                    'pending',
                    'late',
                ])
                ->update([
                    'status' => 'refinanced',
                ]);

            return $newCredit;
        });
    }
}
