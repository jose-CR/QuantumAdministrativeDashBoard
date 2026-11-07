<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * `id` no va aquí: permitirlo dejaba crear registros con un id elegido a
     * mano y desfasaba la secuencia de Postgres (el error de "id duplicado").
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
