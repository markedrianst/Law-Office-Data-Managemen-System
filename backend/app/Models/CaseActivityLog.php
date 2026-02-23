<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseActivityLog extends Model
{
    protected $fillable = [
        'case_id',
        'user_id',
        'action',
        'details',
    ];

    public function case()
    {
        return $this->belongsTo(Cases::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}