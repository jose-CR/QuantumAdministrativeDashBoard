<?php

namespace Database\Seeders;

use App\Models\Installment;
use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Installment::query()
            ->inRandomOrder()
            ->take(100)
            ->get()
            ->each(function (Installment $installment) {

                Payment::factory()->create([
                    'installment_id' => $installment->id,
                    'amount' => $installment->amount,
                ]);

                $installment->update([
                    'paid_at' => now(),
                    'remaining_balance' => 0,
                    'status' => 'paid',
                ]);
            });
    }
}
