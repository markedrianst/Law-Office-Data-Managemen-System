<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderMovement extends Model
{
    protected $table = 'folder_tracker';

    protected $fillable = [
        'case_id',
        'recorded_by',
        'type',
        'from_to',
        'date',
        'purpose',
        'handled_by',
        'is_current',
        // Approval columns (added via 2026_03_06 migration)
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date'        => 'date',
        'is_current'  => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    // Used by controllers and eager loads as ->recorder
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Alias kept for any legacy code that calls ->recordedBy
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Used by controllers and eager loads as ->approver
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Alias kept for any legacy code that calls ->approvedBy
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}