<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    /** @use HasFactory<\Database\Factories\CreditFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',              // Cliente dueño del crédito
        'article_unit_id',       // Vehículo o artículo financiado
        'refinanced_from_id',    // Crédito del cual proviene (si es refinanciamiento)

        'initial_amount',        // Monto inicial del crédito (precio base)
        'down_payment',          // Prima o pago inicial
        'financed_amount',       // Monto que realmente se financia (sin prima)

        'installments',          // Cantidad total de cuotas
        'installment_amount',    // Monto fijo de cada cuota

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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function articleUnit()
    {
        return $this->belongsTo(ArticleUnit::class);
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

    public function originalCredit()
    {
        return $this->belongsTo(Credit::class, 'refinanced_from_id');
    }

    public function refinancings()
    {
        return $this->hasMany(Credit::class, 'refinanced_from_id');
    }
}
