<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Inflow extends Model
{
    /** @use HasFactory<\Database\Factories\InflowFactory> */
    use HasFactory;

    protected $table = 'inflows';

    protected $fillable = [
        'invoice_number', 'date', 'description', 'customer_id', 'amount', 'amount_paid',
        'payment_method', 'transfer_number', 'bank_id', 'transfer_date',
        'payment_status', 'salesperson', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * El monto cobrado manda: el estado se deriva de él.
     *
     * Antes el estado era un select suelto y se podía marcar "parcial" sin decir
     * cuánto entró, así que el reporte no podía calcular el saldo.
     */
    protected static function booted(): void
    {
        static::saving(function (Inflow $inflow): void {
            $inflow->normalizePayment();
        });

        // Las líneas se van por FK en cascada, y esa cascada no dispara eventos:
        // sin esto el stock de una venta no vuelve al borrar la entrada.
        static::deleting(function (Inflow $inflow): void {
            $inflow->items()->get()->each(fn (FlowItem $item) => $item->delete());
            $inflow->attachments()->get()->each(fn (Attachment $adjunto) => $adjunto->delete());
        });
    }

    protected function normalizePayment(): void
    {
        $total = round((float) $this->amount, 2);
        $cobrado = round((float) ($this->amount_paid ?? 0), 2);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('ui.validation.amount_positive'),
            ]);
        }

        // Datos viejos o importaciones que traen el estado sin el monto.
        if ($this->payment_status === 'paid' && $cobrado <= 0) {
            $cobrado = $total;
        }

        if ($cobrado < 0) {
            throw ValidationException::withMessages([
                'amount_paid' => __('ui.validation.paid_not_negative'),
            ]);
        }

        if ($cobrado > $total) {
            throw ValidationException::withMessages([
                'amount_paid' => __('ui.validation.paid_over_amount'),
            ]);
        }

        $this->amount_paid = $cobrado;

        $this->payment_status = match (true) {
            $cobrado <= 0 => 'pending',
            $cobrado >= $total => 'paid',
            default => 'partial',
        };
    }

    /**
     * Lo que falta cobrar de esta entrada.
     */
    public function getReceivableAttribute(): float
    {
        return round((float) $this->amount - (float) $this->amount_paid, 2);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }    

    /**
     * Artículos de la venta: son los que descuentan inventario.
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
