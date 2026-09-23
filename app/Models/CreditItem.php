<?php

namespace App\Models;

use App\Support\ElSalvadorCatalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditItem extends Model
{
    /** @use HasFactory<\Database\Factories\CreditItemFactory> */
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'item_type',
        'item_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return match (true) {
            $this->item instanceof ArticleUnit =>
                $this->item->display_name,

            $this->item instanceof Transportation =>
                collect([
                    ElSalvadorCatalogo::locationLabel($this->item->department, $this->item->municipality, $this->item->district,)
                ])
                    ->filter()
                    ->implode(' • '),

            default =>
                'Elemento no identificado',
        };
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function item()
    {
        return $this->morphTo();
    }
}
