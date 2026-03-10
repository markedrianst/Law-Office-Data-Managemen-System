<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'request_type',
        'requested_by',
        'approved_by',
        'status',
        'request_data',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'approved_at' => 'datetime',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }
}
