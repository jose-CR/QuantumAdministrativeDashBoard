<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientReference extends Model
{
    /** @use HasFactory<\Database\Factories\ClientReferenceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'reference_type',
        'full_name',
        'relationship',
        'phone',
        'address',
        'occupation',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
