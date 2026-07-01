<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\PaymentHistory;
use App\Services\ApplyPaymentService;
use Illuminate\Database\Seeder;

class PaymentHistorySeeder extends Seeder
{
    public function run(): void
    {
        Credit::all()->each(function (Credit $credit) {

            $scenario = fake()->randomElement([
                'none',
                'partial',
                'full',
            ]);

            if ($scenario === 'none') {
                return;
            }

            if ($scenario === 'partial') {

                $amount = fake()->randomFloat(
                    2,
                    1,
                    $credit->pending_balance - 1
                );

                $previousBalance = $credit->pending_balance;

                PaymentHistory::create([
                    'credit_id' => $credit->id,
                    'amount' => $amount,
                    'payment_method' => fake()->randomElement([
                        'cash',
                        'bank_transfer',
                        'card',
                    ]),
                    'payment_date' => now(),
                    'receipt_number' => fake()->numerify(
                        'REC-#####'
                    ),
                    'previous_balance' => $previousBalance,

                    'new_balance' => max(
                        0,
                        $previousBalance - $amount
                    ),
                    'notes' => fake()->sentence(),
                ]);

                app(
                    ApplyPaymentService::class
                )->apply(
                    $credit,
                    $credit->pending_balance
                );

                return;
            }
        });
    }
}
