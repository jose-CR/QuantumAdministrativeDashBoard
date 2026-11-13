<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentHistoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'credit_id',
        'user_id',
        'bank_id',
        'amount',
        'payment_method',
        'payment_date',
        'receipt_number', 
        'previous_balance',
        'new_balance',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    /**
     * Los pagos son la fuente de verdad: si uno desaparece o cambia de monto,
     * las cuotas se rehacen. Antes borrar un pago dejaba el crédito pagado
     * para siempre, sin forma de corregirlo desde el panel.
     */
    protected static function booted(): void
    {
        static::deleted(function (PaymentHistory $pago): void {
            if ($credito = $pago->credit) {
                app(\App\Services\Credits\RecalculateCreditService::class)->execute($credito);
            }
        });

        static::updated(function (PaymentHistory $pago): void {
            if (
                $pago->wasChanged(['amount', 'payment_date'])
                && ($credito = $pago->credit)
            ) {
                app(\App\Services\Credits\RecalculateCreditService::class)->execute($credito);
            }
        });
    }

    /**
     * Quién cargó el pago (los históricos quedaron sin usuario).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
