<?php
// app/Models/LoginLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    // If you don't want timestamps, keep this false
    public $timestamps = false;

    protected $table = 'login_logs';

    protected $fillable = [
        'user_id',
        'email_attempted',
        'ip_address',
        'action',
        'user_agent',
        'status',
        'created_at',  // You're manually setting this
        'details'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    /**
     * Get the user that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}