<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

/**
 * Línea de una entrada o salida de caja: qué artículo y cuántos.
 *
 * Una entrada es una venta (el stock baja) y una salida es una compra (el stock
 * sube). Solo se admiten artículos que se llevan por cantidad: las unidades con
 * serie se asignan a un crédito, no a una entrada suelta.
 */
class FlowItem extends Model
{
    /** @use HasFactory<\Database\Factories\FlowItemFactory> */
    use HasFactory;

    protected $fillable = [
        'flow_type',
        'flow_id',
        'article_id',
        'quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FlowItem $item): void {
            $item->ensureStockAvailable();
        });

        static::created(fn (FlowItem $item) => $item->moveStock(1));

        static::deleted(fn (FlowItem $item) => $item->moveStock(-1));

        static::updated(function (FlowItem $item): void {
            if (! $item->wasChanged(['article_id', 'quantity', 'flow_type'])) {
                return;
            }

            // Revertir lo viejo con el artículo viejo y aplicar lo nuevo.
            $item->moveStockFor(
                (string) $item->getOriginal('flow_type'),
                (int) $item->getOriginal('article_id'),
                (int) ($item->getOriginal('quantity') ?: 1),
                -1,
            );

            $item->moveStock(1);
        });
    }

    protected function ensureStockAvailable(): void
    {
        $article = Article::find($this->article_id);

        if (! $article) {
            return;
        }

        if ($article->tracksSerials()) {
            throw ValidationException::withMessages([
                'article_id' => 'Ese artículo se lleva por unidades con serie: no se puede usar en una entrada o salida.',
            ]);
        }

        // Solo una venta descuenta: comprar no puede dejar el stock en negativo.
        if ($this->flow_type !== Inflow::class) {
            return;
        }

        $reserved = $this->exists
            && $this->getOriginal('article_id') == $this->article_id
            ? (int) ($this->getOriginal('quantity') ?: 1)
            : 0;

        $available = (int) $article->stock + $reserved;

        if ((int) $this->quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Solo quedan {$available} de {$article->full_name}.",
            ]);
        }
    }

    /**
     * @param  int  $sign  1 applies the movement, -1 reverts it
     */
    protected function moveStock(int $sign): void
    {
        $this->moveStockFor(
            (string) $this->flow_type,
            (int) $this->article_id,
            (int) $this->quantity,
            $sign,
        );
    }

    protected function moveStockFor(string $flowType, int $articleId, int $quantity, int $sign): void
    {
        if ($quantity <= 0 || $articleId <= 0) {
            return;
        }

        $article = Article::find($articleId);

        if (! $article || ! $article->usesStock()) {
            return;
        }

        // Entrada = venta (resta). Salida = compra (suma).
        $delta = $flowType === Inflow::class ? -$quantity : $quantity;

        Article::query()
            ->whereKey($articleId)
            ->increment('stock', $sign * $delta);
    }

    /**
     * Lo que suma esta línea a la entrada o salida.
     */
    public function getTotalAttribute(): float
    {
        return round((float) $this->price * max(1, (int) $this->quantity), 2);
    }

    public function flow(): MorphTo
    {
        return $this->morphTo();
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
