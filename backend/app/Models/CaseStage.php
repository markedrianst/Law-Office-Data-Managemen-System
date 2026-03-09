<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Cached Lookups ────────────────────────────────────────────────────────

    /**
     * All stages ordered by id, cached for 5 minutes.
     * Used by: CaseController::stages(), index() lookups block.
     */
    public static function cachedAll(): \Illuminate\Support\Collection
    {
        return Cache::remember('case_stages', 300, fn() =>
            static::orderBy('id')->get(['id', 'name', 'is_active'])
        );
    }

    /**
     * ID of the first active stage — used as fallback when no stage is
     * specified on case creation.
     */
    public static function firstActiveId(): ?int
    {
        return static::where('is_active', true)->orderBy('id')->value('id');
    }

    /**
     * Bust the stages cache. Call after create / update / toggle.
     */
    public static function bustCache(): void
    {
        Cache::forget('case_stages');
    }
}