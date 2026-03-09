<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CourtOffice extends Model
{
    use HasFactory;

    protected $table = 'courts';

    protected $fillable = [
        'name', 'type', 'address', 'sort_order','is_active', 'contact_info', 
    ];

    protected $casts = [
        'is_active'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name',    'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // ── Cached Lookups ────────────────────────────────────────────────────────

    /**
     * All active courts/offices ordered by name, cached for 5 minutes.
     * Used by: CaseController index() lookups block.
     */
    public static function cachedActive(): \Illuminate\Support\Collection
    {
        return Cache::remember('courts_active', 300, fn() =>
            static::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
        );
    }

    /**
     * Bust the courts cache. Call after create / update / toggle.
     */
    public static function bustCache(): void
    {
        Cache::forget('courts_active');
    }
}