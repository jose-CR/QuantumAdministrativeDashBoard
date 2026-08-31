<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleUnit extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleUnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'color',
        'cash_price', // precio al contado
        'vin',
        'engine_number', // Motor
        'plate', // Placa
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cash_price' => 'decimal:2',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return collect([
            "{$this->article->brand} {$this->article->model} {$this->vin}",
            $this->color,
            $this->plate,
        ])
            ->filter()
            ->implode(' • ');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function creditItems()
    {
        return $this->hasMany(CreditItem::class);
    }
}
