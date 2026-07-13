<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use Illuminate\Database\Seeder;

class InstallmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $generator = app(InstallmentGeneratorService::class);

        $creator = User::first(); // O User::find(1)

        if (! $creator) {
            $this->command->error('No existe ningún usuario.');

            $creator = User::factory()->create([
                'name' => 'Administrador',
                'email' => 'admin@example.com',
            ]);

            $this->command->info('El usuario se a creado para el seeder.');
        }

        Credit::all()->each(function (Credit $credit) use ($generator, $creator) {
            $generator->generate(
                credit: $credit,
                creator: $creator,
                assignedUser: null,
            );
        });
    }
}
