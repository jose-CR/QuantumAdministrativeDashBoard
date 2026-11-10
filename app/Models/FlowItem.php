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
        $articulo = Article::find($this->article_id);

        if (! $articulo) {
            return;
        }

        if ($articulo->tracksSerials()) {
            throw ValidationException::withMessages([
                'article_id' => 'Ese artículo se lleva por unidades con serie: no se puede usar en una entrada o salida.',
            ]);
        }

        // Solo una venta descuenta: comprar no puede dejar el stock en negativo.
        if ($this->flow_type !== Inflow::class) {
            return;
        }

        $reservado = $this->exists
            && $this->getOriginal('article_id') == $this->article_id
            ? (int) ($this->getOriginal('quantity') ?: 1)
            : 0;

        $disponible = (int) $articulo->stock + $reservado;

        if ((int) $this->quantity > $disponible) {
            throw ValidationException::withMessages([
                'quantity' => "Solo quedan {$disponible} de {$articulo->full_name}.",
            ]);
        }
    }

    /**
     * @param  int  $signo  1 aplica el movimiento, -1 lo revierte
     */
    protected function moveStock(int $signo): void
    {
        $this->moveStockFor(
            (string) $this->flow_type,
            (int) $this->article_id,
            (int) $this->quantity,
            $signo,
        );
    }

    protected function moveStockFor(string $tipo, int $articuloId, int $cantidad, int $signo): void
    {
        if ($cantidad <= 0 || $articuloId <= 0) {
            return;
        }

        $articulo = Article::find($articuloId);

        if (! $articulo || ! $articulo->usesStock()) {
            return;
        }

        // Entrada = venta (resta). Salida = compra (suma).
        $delta = $tipo === Inflow::class ? -$cantidad : $cantidad;

        Article::query()
            ->whereKey($articuloId)
            ->increment('stock', $signo * $delta);
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
