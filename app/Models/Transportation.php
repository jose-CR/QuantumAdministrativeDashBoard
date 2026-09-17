<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transportation extends Model
{
    /** @use HasFactory<\Database\Factories\TransportationFactory> */
    use HasFactory;

    protected $fillable = [
        'department',
        'municipality',
        'district',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function creditItems()
    {
        return $this->morphMany(CreditItem::class, 'item');
    }
}
