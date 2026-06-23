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
        'client_id',
        'article_unit_id',
        'refinanced_from_id',
        'initial_amount',
        'down_payment',
        'financed_amount',
        'installments',
        'installment_amount',
        'periodicity',
        'interest_rate',
        'total_interest',
        'total_amount',
        'pending_balance',
        'start_date',
        'payment_day',
        'payment_month',
        'status',
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
