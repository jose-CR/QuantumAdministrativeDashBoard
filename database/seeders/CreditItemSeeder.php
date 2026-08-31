<?php

namespace Database\Seeders;

use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Models\CreditItem;
use Illuminate\Database\Seeder;

class CreditItemSeeder extends Seeder
{
    public function run(): void
    {
        $credits = Credit::all();

        $articleUnits = ArticleUnit::where('status', 'available')
            ->get();

        if ($credits->isEmpty()) {
            return;
        }

        if ($articleUnits->count() < $credits->count()) {
            $this->command->error(
                'No hay suficientes ArticleUnit disponibles para todos los créditos.'
            );

            return;
        }

        foreach ($credits as $credit) {

            $unit = $articleUnits->shift();

            CreditItem::create([
                'credit_id' => $credit->id,
                'article_unit_id' => $unit->id,
                'price' => $unit->cash_price,
            ]);

            $unit->update([
                'status' => 'sold',
            ]);
        }
    }
}
