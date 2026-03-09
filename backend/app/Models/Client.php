<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Client extends Model
{
    protected $fillable = [
        'full_name',
        'contact_no',
        'email',
        'address',
    ];

    public function cases()
    {
        return $this->hasMany(Cases::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Search by full_name (case-insensitive LIKE).
     */
    public function scopeSearch($query, ?string $term)
    {
        return $term
            ? $query->where('full_name', 'like', '%' . $term . '%')
            : $query;
    }

    // ── Cached Lookups ────────────────────────────────────────────────────────

    /**
     * All clients ordered by name, cached for 1 minute.
     * Used by: CaseController index() lookups block.
     */
    public static function cachedAll(): \Illuminate\Support\Collection
    {
        return Cache::remember('clients_all', 60, fn() =>
            static::orderBy('full_name')
                ->get(['id', 'full_name', 'contact_no', 'email'])
        );
    }

    /**
     * Bust the clients cache. Call after create / update.
     */
    public static function bustCache(): void
    {
        Cache::forget('clients_all');
    }
}