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

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }
}
