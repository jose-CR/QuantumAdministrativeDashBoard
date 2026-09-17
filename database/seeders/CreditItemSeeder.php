<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\CreditItem;
use Illuminate\Database\Seeder;

class CreditItemSeeder extends Seeder
{
    public function run(): void
    {
        $credits = Credit::all();

        foreach ($credits as $credit) {
            CreditItem::factory()
                ->for($credit)
                ->vehicle()
                ->create();

            CreditItem::factory()
                ->for($credit)
                ->transportation()
                ->create();
        }
    }
}
