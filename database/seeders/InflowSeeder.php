<?php

namespace Database\Seeders;

use App\Models\Inflow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inflow::factory()
            ->count(30)
            ->create();
    }
}
