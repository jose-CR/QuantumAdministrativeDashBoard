<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditItem extends Model
{
    /** @use HasFactory<\Database\Factories\CreditItemFactory> */
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'article_unit_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function articleUnit()
    {
        return $this->belongsTo(ArticleUnit::class);
    }
}
