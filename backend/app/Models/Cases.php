<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cases extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'case_no',
        'case_code',
        'title',
        'category_id',
        'client_id',
        'court_or_office',
        'docket_no',
        'assigned_lawyer_id',
        'assigned_clerk_id',
        'priority',
        'case_status',
        'current_stage_id',
        'summary',
        'created_by',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
    }

    public function clerk(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_clerk_id');
    }

    public function currentStage(): BelongsTo
    {
        // Assumes a CaseStage model; swap class name if yours differs
        return $this->belongsTo(\App\Models\CaseStage::class, 'current_stage_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CaseActivityLog::class, 'case_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(\App\Models\CaseStageHistory::class, 'case_id');
    }
}