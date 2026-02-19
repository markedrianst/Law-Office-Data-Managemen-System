<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email_attempted',
        'ip_address',
        'user_agent',
        'status',
        'created_at'
    ];
}
