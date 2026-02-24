<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStage extends Model
{
    protected $fillable = ['name', 'order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function cases()
    {
        return $this->hasMany(Cases::class, 'current_stage_id');
    }

    public function histories()
    {
        return $this->hasMany(CaseStageHistory::class, 'to_stage_id');
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Save this second class in: app/Models/CaseStageHistory.php
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStageHistory extends Model
{
    protected $fillable = [
        'case_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'remarks',
    ];

    public function case()
    {
        return $this->belongsTo(Cases::class);
    }

    public function fromStage()
    {
        return $this->belongsTo(CaseStage::class, 'from_stage_id');
    }

    public function toStage()
    {
        return $this->belongsTo(CaseStage::class, 'to_stage_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
