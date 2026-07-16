<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        Bank::create([
            'name' => 'BAC Credomatic',
        ]);

        Bank::create([
            'name' => 'Banco Agricola',
        ]);

        Bank::create([
            'name' => 'Banco Cuscatlan',
        ]);   
    }
}
