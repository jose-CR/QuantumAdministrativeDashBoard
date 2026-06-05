<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    /** @use HasFactory<\Database\Factories\ClientsFactory> */
    use HasFactory;

    public function references()
    {
        return $this->hasMany(Reference::class);
    }
}
