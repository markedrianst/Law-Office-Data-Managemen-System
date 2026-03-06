<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'case_checklists';

    protected $fillable = [
        'case_id',
        'created_by',
        'task',
        'status',
        'due_date',
        'assigned_to',
        'assigned_clerk_id',
        'notes',
        'completed_at',
        'is_out',
    ];

    protected $casts = [
        'due_date'     => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'is_out'       => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForCase($query, int $caseId)
    {
        return $query->where('case_id', $caseId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Auto-set completed_at when status → done ───────────────────────────

    protected static function booted(): void
    {
        static::saving(function (CaseChecklist $item) {
            if ($item->isDirty('status')) {
                $item->completed_at = $item->status === 'done' ? now() : null;
            }
        });
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// ADD THIS to your existing Cases model (app/Models/Cases.php):
// ─────────────────────────────────────────────────────────────────────────────
//
//    public function checklists()
//    {
//        return $this->hasMany(\App\Models\CaseChecklist::class, 'case_id');
//    }
//
// ─────────────────────────────────────────────────────────────────────────────