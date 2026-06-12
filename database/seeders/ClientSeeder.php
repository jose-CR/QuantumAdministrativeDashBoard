<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Client;
use App\Models\ClientReference;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::factory()
            ->count(50)
            ->create()
            ->each(function (Client $client) {

                ClientReference::factory()
                    ->count(3)
                    ->create([
                        'client_id' => $client->id,
                        'reference_type' => 'family',
                    ]);

                ClientReference::factory()
                    ->count(3)
                    ->create([
                        'client_id' => $client->id,
                        'reference_type' => 'friend',
                    ]);
            });
    }
}
