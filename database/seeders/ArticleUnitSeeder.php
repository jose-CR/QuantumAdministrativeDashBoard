<?php

namespace Database\Seeders;

use App\Models\ArticleUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArticleUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ArticleUnit::factory(200)->create();
    }
}
