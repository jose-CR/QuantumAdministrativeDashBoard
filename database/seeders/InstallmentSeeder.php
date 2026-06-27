<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Services\InstallmentGeneratorService;
use Illuminate\Database\Seeder;

class InstallmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $generator = app(
            InstallmentGeneratorService::class
        );

        Credit::all()->each(
            fn (Credit $credit)
                => $generator->generate($credit)
        );
    }
}
