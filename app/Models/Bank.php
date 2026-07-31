<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
    */
    protected $fillable = [
        'id',
        'name',
    ];

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }
}
