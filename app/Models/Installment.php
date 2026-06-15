<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    /** @use HasFactory<\Database\Factories\InstallmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'credit_id',
        'number',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'remaining_balance',
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

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
