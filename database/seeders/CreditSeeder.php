<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreditSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();

        foreach ($customers as $customer) {

            Credit::factory()->create([
                'customer_id' => $customer->id,
            ]);
        }
    }
}
