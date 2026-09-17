<?php

namespace Database\Factories;

use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\Transportation;
use Database\Factories\Concerns\InteractsWithModels;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditItem>
 */
class CreditItemFactory extends Factory
{
    use InteractsWithModels;

    protected $model = CreditItem::class;

    public function definition(): array
    { 
        return [
            'credit_id' => Credit::factory(),
            'item_type' => null,
            'item_id' => null,
            'price' => 0,
        ];
    }
    
    public function vehicle(): static
    {
        $articleUnit = $this->randomItem(
            ArticleUnit::class,
            fn ($query) => $query->where('status', 'available')
        );

        $articleUnit->update([
            'status' => 'sold',
        ]);

        return $this->state([
            'item_type' => ArticleUnit::class,
            'item_id' => $articleUnit->id,
            'price' => $articleUnit->cash_price,
        ]);
    }

    public function transportation(): static
    {
        $transportation = Transportation::factory()->create();

        return $this->state([
            'item_type' => Transportation::class,
            'item_id' => $transportation->id,
            'price' => $transportation->price,
        ]);
    }
}
