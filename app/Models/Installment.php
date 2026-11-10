<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Installment extends Model
{
    /** @use HasFactory<\Database\Factories\InstallmentFactory> */
    use HasFactory;

    public const UNPAID_STATUSES = ['pending', 'late'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'credit_id',           // ID del crédito al que pertenece esta cuota
        'number',              // Número de la cuota dentro del crédito (ej: 1, 2, 3...)
        'amount',              // Monto total que debe pagarse en esta cuota
        'due_date',            // Fecha de vencimiento de la cuota

        'paid_at',             // Fecha en la que la cuota fue pagada completamente
        'status',              // Estado de la cuota: pending, partial, paid, cancelled

        'remaining_balance',   // Saldo pendiente de esta cuota (lo que falta por pagar)
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'remaining_balance' => 'decimal:2',

            'due_date' => 'date',
            'paid_at'  => 'date',
        ];
    }


    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::UNPAID_STATUSES)
            ->whereDate('due_date', '<', today());
    }

    /**
     * Borrar una cuota cambia el saldo del crédito: hay que recalcularlo, porque
     * antes quedaba un saldo que ya no correspondía a ninguna cuota.
     */
    protected static function booted(): void
    {
        static::deleted(function (Installment $installment): void {
            $credito = $installment->credit;

            if (! $credito) {
                return;
            }

            $credito->update([
                'pending_balance' => round(
                    (float) $credito->installments()->sum('remaining_balance'),
                    2
                ),
            ]);

            app(\App\Services\Credits\CloseCreditService::class)->execute($credito);
        });
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }
}
