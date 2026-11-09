<?php

namespace App\Models;

use App\Support\ElSalvadorCatalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditItem extends Model
{
    /** @use HasFactory<\Database\Factories\CreditItemFactory> */
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'item_type',
        'item_id',
        'price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /**
     * El stock de un artículo por cantidad se mueve con el crédito: sale cuando
     * el bien entra al crédito y vuelve si el ítem se borra o cambia.
     */
    protected static function booted(): void
    {
        static::created(function (CreditItem $item): void {
            $item->moveStock(-$item->quantityInStock());
        });

        static::deleted(function (CreditItem $item): void {
            $item->moveStock($item->quantityInStock());
        });

        static::updated(function (CreditItem $item): void {
            if (! $item->wasChanged(['item_type', 'item_id', 'quantity'])) {
                return;
            }

            // Devolver lo que se había descontado del ítem anterior y descontar
            // lo del nuevo: el repeater puede cambiar de artículo, no solo la cantidad.
            $item->moveStockFor(
                $item->getOriginal('item_type'),
                (int) $item->getOriginal('item_id'),
                (int) ($item->getOriginal('quantity') ?: 1),
            );

            $item->moveStock(-$item->quantityInStock());
        });
    }

    /**
     * Cuánto stock mueve este ítem: solo los artículos por cantidad mueven algo.
     */
    public function quantityInStock(): int
    {
        return $this->item_type === Article::class
            ? max(1, (int) $this->quantity)
            : 0;
    }

    protected function moveStock(int $delta): void
    {
        $this->moveStockFor($this->item_type, (int) $this->item_id, $delta);
    }

    protected function moveStockFor(?string $type, int $id, int $delta): void
    {
        if ($delta === 0 || $type !== Article::class) {
            return;
        }

        $articulo = Article::find($id);

        // Las unidades con serie ya salieron del inventario al asignarse a la unidad:
        // descontar stock ahí contaría dos veces.
        if (! $articulo || ! $articulo->usesStock()) {
            return;
        }

        $articulo->update(['stock' => (int) $articulo->stock + $delta]);
    }

    /**
     * Lo que suma este ítem al monto del crédito.
     */
    public function getTotalAttribute(): float
    {
        return round((float) $this->price * max(1, (int) $this->quantity), 2);
    }

    public function getDisplayNameAttribute(): string
    {
        return match (true) {
            $this->item instanceof ArticleUnit =>
                $this->item->display_name,

            // Artículo por cantidad: el nombre solo no dice cuántos se llevaron.
            $this->item instanceof Article =>
                ((int) $this->quantity > 1 ? (int) $this->quantity . ' × ' : '')
                    . $this->item->full_name,

            $this->item instanceof Transportation =>
                collect([
                    ElSalvadorCatalogo::locationLabel($this->item->department, $this->item->municipality, $this->item->district,)
                ])
                    ->filter()
                    ->implode(' • '),

            default =>
                'Elemento no identificado',
        };
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function item()
    {
        return $this->morphTo();
    }
}
