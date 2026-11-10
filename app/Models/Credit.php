<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credit extends Model
{
    /** @use HasFactory<\Database\Factories\CreditFactory> */
    use HasFactory;

    /**
     * Al borrar el crédito, sus ítems se borran con la FK en cascada, y la cascada
     * de la base **no** dispara los eventos del modelo: sin esto el stock de un
     * artículo por cantidad nunca vuelve al inventario.
     */
    protected static function booted(): void
    {
        static::deleting(function (Credit $credit): void {
            // El estado guardado, no el de la instancia: un objeto viejo en memoria
            // puede seguir diciendo 'active' cuando en la base ya está refinanciado.
            $estado = Credit::query()->whereKey($credit->getKey())->value('status')
                ?? $credit->status;

            /*
             * Un crédito refinanciado ya no tiene los bienes: viven en el crédito
             * nuevo. Si se borra, hay que sacar sus ítems SIN disparar los eventos,
             * porque devolver el stock lo contaría dos veces.
             */
            if ($estado === 'refinanced') {
                CreditItem::withoutEvents(
                    fn () => $credit->items()->delete()
                );

                return;
            }

            $credit->items()
                ->get()
                ->each(fn (CreditItem $item) => $item->delete());
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',              // Cliente dueño del crédito
        'refinanced_from_id',    // Crédito del cual proviene (si es refinanciamiento)

        'initial_amount',        // Monto inicial del crédito (precio base)
        'down_payment',          // Prima o pago inicial
        'financed_amount',       // Monto que realmente se financia (sin prima)

        'installments',          // Cantidad total de cuotas
        'installment_amount',    // Monto fijo de cada cuota
        'interest_type',         // 'auto' = sale del cálculo, 'fixed' = tasa pactada

        'periodicity',           // Frecuencia de pago (mensual, semanal, etc.)
        'interest_rate',         // Tasa de interés aplicada
        'total_interest',        // Total de interés generado en el crédito

        'total_amount',          // Total final a pagar (capital + interés)
        'pending_balance',       // Saldo pendiente total del crédito

        'start_date',            // Fecha de inicio del crédito
        'payment_day',           // Día de pago asignado
        'payment_month',         // Mes de pago (si aplica)

        'status',                // Estado del crédito (activo, cerrado, refinanciado, etc.)
    ];

    /**
     * Valores por defecto: un crédito creado por código (import, seeder, prueba)
     * sin interés explícito usa el calculado.
     */
    protected $attributes = [
        'interest_type' => 'auto',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_amount'     => 'decimal:2',
            'down_payment'       => 'decimal:2',
            'financed_amount'    => 'decimal:2',
            'installments' => 'integer',
            'installment_amount' => 'decimal:2',
            'interest_rate'      => 'decimal:2',
            'total_interest'     => 'decimal:2',
            'total_amount'       => 'decimal:2',
            'pending_balance'    => 'decimal:2',

            'start_date' => 'date',

            'payment_day'   => 'integer',
            'payment_month'   => 'integer',
        ];
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereHas('installments', fn (Builder $q) => $q->overdue());
    }

    public function client()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function installments()
    {
        return $this->hasMany(
            Installment::class
        );
    }

    public function paymentHistories()
    {
        return $this->hasMany(
            PaymentHistory::class
        );
    }

    public function latestPayments()
    {
        return $this->hasMany(PaymentHistory::class)
            ->latest('payment_date');
    }

    public function originalCredit()
    {
        return $this->belongsTo(Credit::class, 'refinanced_from_id');
    }

    public function refinancings()
    {
        return $this->hasMany(Credit::class, 'refinanced_from_id');
    }

    /**
     * Facturas y fotos del crédito (la boleta firmada, la foto del bien, etc.).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function items()
    {
        return $this->hasMany(CreditItem::class);
    }

    public function firstItem()
    {
        return $this->hasOne(CreditItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
