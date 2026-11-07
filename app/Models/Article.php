<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    /** Se controla por cantidad: no hay unidades, hay stock. */
    public const TRACKING_NONE = 'none';

    /** Se controla pieza por pieza, cada una con su serie/VIN. */
    public const TRACKING_SERIAL = 'serial';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'tracking',
        'brand', // marca (opcional: un casco puede no tener una marca registrada)
        'model', // modelo (opcional)
        'year',  // año (solo tiene sentido en vehículos)
        'cash_price', // precio de lista
        'stock', // existencias cuando el artículo no maneja series
        'description',
    ];

    protected $attributes = [
        'tracking' => self::TRACKING_NONE,
        'stock' => 0,
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
            'stock' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // El nombre es la identidad del artículo: si no lo escriben, se arma con
        // marca, modelo y año, que es como se busca un vehículo en el taller.
        static::saving(function (Article $article) {
            if (blank($article->name)) {
                $article->name = $article->composeName();
            }
        });
    }

    public function composeName(): string
    {
        $name = preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $this->brand,
            $this->model,
            $this->year,
        ], fn ($part) => filled($part))));

        return trim((string) $name) ?: 'Artículo sin nombre';
    }

    public function getFullNameAttribute(): string
    {
        return (string) ($this->name ?: $this->composeName());
    }

    /**
     * ¿Cada pieza se guarda como unidad con su número de serie?
     */
    public function tracksSerials(): bool
    {
        return $this->tracking === self::TRACKING_SERIAL;
    }

    /**
     * ¿El inventario se lleva por cantidad (sin unidades)?
     */
    public function usesStock(): bool
    {
        return ! $this->tracksSerials();
    }

    public function scopeSerialTracked(Builder $query): Builder
    {
        return $query->where('tracking', self::TRACKING_SERIAL);
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
