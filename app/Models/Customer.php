<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

        /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'document_number',
        'full_name',
        'email',
        'phone_primary',
        'phone_secondary',
        'nrc',
        'economic_activity',
        'department',
        'municipality',
        'district',
        'address',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            
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

    public function latestCredit()
    {
        return $this->hasOne(Credit::class)
            ->latestOfMany('created_at');
    }
}
