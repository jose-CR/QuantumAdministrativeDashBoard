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
        'vin',
        'engine_number', // Motor
        'plate', // Placa
        'color',
        'status',
    ];

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

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }
}
