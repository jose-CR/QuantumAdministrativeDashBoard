<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Installment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        Installment::all()->each(function (
            Installment $installment
        ) {

            $scenario = fake()->randomElement([
                'none',
                'partial',
                'full',
            ]);

            if ($scenario === 'none') {
                return;
            }

            if ($scenario === 'partial') {

                $paidAmount = fake()->randomFloat(
                    2,
                    1,
                    $installment->amount - 1
                );

                Payment::create([
                    'installment_id' => $installment->id,
                    'amount' => $paidAmount,
                    'payment_date' => now(),
                    'receipt_number' => fake()->numerify(
                        'REC-#####'
                    ),
                ]);

                $installment->update([
                    'remaining_balance'
                        => $installment->amount - $paidAmount,
                ]);

                return;
            }

            Payment::create([
                'installment_id' => $installment->id,
                'amount' => $installment->amount,
                'payment_date' => now(),
                'receipt_number' => fake()->numerify(
                    'REC-#####'
                ),
            ]);

            $installment->update([
                'remaining_balance' => 0,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });
    }
}