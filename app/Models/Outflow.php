<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outflow extends Model
{
    /** @use HasFactory<\Database\Factories\OutflowFactory> */
    use HasFactory;

    protected $table = 'outflows';

    protected $fillable = [
        'invoice_date', 'company', 'invoice_code', 'quantity',
        'amount', 'description', 'source', 'area',
    ];

    protected $attributes = [
        'source' => 'Caja',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Outflow $outflow) {
            // Las líneas mueven stock: se borran con eventos para devolverlo.
            $outflow->items()->get()->each(fn (FlowItem $item) => $item->delete());
            $outflow->attachments->each->delete();
        });
    }

    /**
     * Artículos de la compra: son los que suman inventario.
     */
    public function items()
    {
        return $this->morphMany(FlowItem::class, 'flow');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function receipts()
    {
        return $this->attachments()->where('collection', 'receipts');
    }
}
