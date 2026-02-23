<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'full_name',
        'contact_no',
        'email',
        'address',
    ];

    public function cases()
    {
        return $this->hasMany(Cases::class);
    }
}