<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseStage extends Model
{
    protected $table = 'case_stages';

    protected $fillable = [
        'name',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(Cases::class, 'current_stage_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(CaseStageHistory::class, 'to_stage_id');
    }
}
