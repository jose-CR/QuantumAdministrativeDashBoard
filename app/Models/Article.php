<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'brand', // marca 
        'model',
        'year',
        'color',
        'cash_price', // Precio Contado
        'credit_price', // Precio Crédito
        'descripcion'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'cash_price' => 'decimal:2',
            'credit_price' => 'decimal:2',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model}";
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function units()
    {
        return $this->hasMany(ArticleUnit::class);
    }
}
