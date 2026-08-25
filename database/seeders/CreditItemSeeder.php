<?php

namespace Database\Seeders;

use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Models\CreditItem;
use Illuminate\Database\Seeder;

class CreditItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $credits = Credit::all();

        $articleUnits = ArticleUnit::where('status', 'available')
            ->get();

        if ($credits->isEmpty() || $articleUnits->isEmpty()) {
            return;
        }

        foreach ($credits as $credit) {

            $numberOfUnits = min(
                rand(1, 2),
                $articleUnits->count()
            );

            $units = $articleUnits->random($numberOfUnits);

            foreach ($units as $unit) {

                CreditItem::create([
                    'credit_id' => $credit->id,
                    'article_unit_id' => $unit->id,
                    'price' => $unit->cash_price,
                ]);

                $unit->update([
                    'status' => 'sold',
                ]);
            }

            $articleUnits = $articleUnits
                ->reject(
                    fn ($articleUnit) =>
                        $units->contains('id', $articleUnit->id)
                )
                ->values();

            if ($articleUnits->isEmpty()) {
                break;
            }
        }
    }
}