<?php

namespace App\Models;

use App\Models\Concerns\HasCreditBlockedDeletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasCreditBlockedDeletion, HasFactory;

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

        /*
         * Borrar un artículo se lleva sus unidades. La FK es RESTRICT, así que
         * sin esto habría que borrarlas a mano.
         *
         * Se cancela si alguna unidad está en un crédito: `credit_items` apunta
         * por relación polimórfica, sin FK, y quedaría apuntando a la nada.
         */
        static::deleting(function (Article $article): bool {
            if (! $article->canBeDeletedWithChildren()) {
                return false;
            }

            ArticleUnit::query()->where('article_id', $article->id)->delete();

            return true;
        });
    }

    /**
     * Qué se lleva por delante borrar este artículo.
     *
     * @return array{units: int, units_in_credits: int}
     */
    public function deletionSummary(): array
    {
        $units = $this->units();

        return [
            'units' => $units->count(),
            'units_in_credits' => ArticleUnit::query()
                ->where('article_id', $this->id)
                ->whereIn('id', CreditItem::query()
                    ->select('item_id')
                    ->where('item_type', ArticleUnit::class))
                ->count(),
        ];
    }

    public function canBeDeletedWithChildren(): bool
    {
        return $this->deletionSummary()['units_in_credits'] === 0;
    }

    /**
     * Créditos que impiden el borrado, para poder buscarlos: id y cliente.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, customer: string}>
     */
    public function creditsBlockingDeletion(): \Illuminate\Support\Collection
    {
        return Credit::query()
            ->whereIn('id', CreditItem::query()
                ->select('credit_id')
                ->where('item_type', ArticleUnit::class)
                ->whereIn('item_id', $this->units()->select('id')))
            ->with('client:id,full_name')
            ->orderBy('id')
            ->get()
            ->map(fn (Credit $credit): array => [
                'id' => $credit->id,
                'customer' => (string) ($credit->client?->full_name ?? ''),
            ]);
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
