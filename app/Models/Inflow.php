<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inflow extends Model
{
    /** @use HasFactory<\Database\Factories\InflowFactory> */
    use HasFactory;

    protected $table = 'inflows';

    protected $fillable = [
        'invoice_number', 'date', 'description', 'customer_id', 'amount',
        'payment_method', 'transfer_number', 'bank_id', 'transfer_date',
        'payment_status', 'salesperson', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }    

}
