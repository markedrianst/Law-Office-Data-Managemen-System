<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class CaseCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name', 
        'sort_order', 
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true
    ];

    /**
     * Get the cases for the category.
     */
    public function cases()
    {
        return $this->hasMany(Cases::class, 'category_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive categories.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the formatted name with status.
     */
    public function getFormattedNameAttribute(): string
    {
        return $this->name . ($this->is_active ? '' : ' (Inactive)');
    }

    /**
     * Check if category has any cases.
     */
    public function hasCases(): bool
    {
        return $this->cases()->exists();
    }

    /**
     * Get cases count.
     */
    public function getCasesCountAttribute(): int
    {
        return $this->cases()->count();
    }

    // ── Cached Lookups ────────────────────────────────────────────────────────

    /**
     * All categories ordered by name, cached for 5 minutes.
     * Used by: CaseController::categories(), index() lookups block.
     */
    public static function cachedAll(): \Illuminate\Support\Collection
    {
        return Cache::remember('case_categories', 300, fn() =>
            static::orderBy('name')->get(['id', 'name'])
        );
    }

    /**
     * Bust the categories cache. Call after create / update / toggle.
     */
    public static function bustCache(): void
    {
        Cache::forget('case_categories');
    }
}