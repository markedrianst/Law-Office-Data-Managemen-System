<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStageHistory extends Model
{
    protected $table = 'case_stage_histories';

    protected $fillable = [
        'case_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'remarks',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(CaseStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(CaseStage::class, 'to_stage_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
