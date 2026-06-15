<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\Installment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstallmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Credit::all()->each(function (Credit $credit) {

            for ($i = 1; $i <= $credit->installments; $i++) {

                Installment::create([
                    'credit_id' => $credit->id,

                    'number' => $i,

                    'amount' => $credit->installment_amount,

                    'remaining_balance' => $credit->installment_amount,

                    'due_date' => $credit->start_date
                        ->copy()
                        ->addMonths($i),

                    'status' => 'pending',
                ]);
            }
        });
    }
}
