<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseCategory extends Model
{
    protected $fillable = ['name'];

    public function cases()
    {
        return $this->hasMany(Cases::class);
    }
}