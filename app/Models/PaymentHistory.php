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

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
