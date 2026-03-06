<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistMovement extends Model
{
    protected $fillable = [
        'case_id',
        'checklist_id',
        'recorded_by',
        'type',
        'from_to',
        'date',
        'purpose',
        'handled_by',
        'is_current',
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

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(CaseChecklist::class, 'checklist_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}