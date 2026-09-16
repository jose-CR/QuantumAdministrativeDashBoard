<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outflow extends Model
{
    /** @use HasFactory<\Database\Factories\OutflowFactory> */
    use HasFactory;

    protected $table = 'outflows';

    protected $fillable = [
        'invoice_date', 'company', 'invoice_code', 'quantity',
        'amount', 'description', 'source', 'area',
    ];

    protected $attributes = [
        'source' => 'Caja',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
