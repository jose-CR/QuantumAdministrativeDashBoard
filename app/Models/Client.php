<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'identity_document',
        'birth_date',
        'gender',
        'phone_primary',
        'phone_secondary',
        'email',
        'address',
        'occupation',
        'workplace',
        'monthly_income',
        'marital_status',
        'nationality',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'monthly_income' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function references()
    {
        return $this->hasMany(ClientReference::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function activeCredit()
    {
        return $this->hasOne(Credit::class)
            ->where('status', 'active');
    }
}
