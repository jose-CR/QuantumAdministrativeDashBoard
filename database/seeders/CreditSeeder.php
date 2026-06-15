<?php

namespace Database\Seeders;

use App\Models\ArticleUnit;
use App\Models\Client;
use App\Models\Credit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        $units = ArticleUnit::query()
            ->where('status', 'available')
            ->get();

        foreach ($clients as $client) {

            if ($units->isEmpty()) {
                break;
            }

            $unit = $units->shift();

            Credit::factory()->create([
                'client_id' => $client->id,
                'article_unit_id' => $unit->id,
            ]);

            $unit->update([
                'status' => 'sold',
            ]);
        }
    }
}
