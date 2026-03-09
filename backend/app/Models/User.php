<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'full_name',
        'email',
        'password_hash',
        'status',
        'last_login',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

      public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = bcrypt($value);
    }

    // Role relationship
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Filter to active users with a lawyer or clerk role.
     * Optionally narrow to a single role ('lawyer' or 'clerk').
     */
    public function scopeAssignable($query, ?string $role = null)
    {
        return $query
            ->where('status', 'active')
            ->whereHas('role', fn($r) =>
                $role && in_array($role, ['lawyer', 'clerk'])
                    ? $r->where('name', $role)
                    : $r->whereIn('name', ['lawyer', 'clerk'])
            );
    }

    // ── Cached Lookups ────────────────────────────────────────────────────────

    /**
     * All assignable users (lawyers + clerks), cached for 5 minutes.
     * Returns a Collection of plain arrays: [id, name, role].
     * Used by: CaseController::assignableUsers(), index() lookups block.
     */
    public static function cachedAssignable(?string $role = null, int $limit = 100): \Illuminate\Support\Collection
    {
        $cacheKey = 'assignable_users_' . ($role ?? 'all') . "_{$limit}";

        return Cache::remember($cacheKey, 300, fn() =>
            static::assignable($role)
                ->select('id', 'full_name', 'role_id')
                ->orderBy('full_name')
                ->limit($limit)
                ->get()
                ->map(fn($u) => [
                    'id'   => $u->id,
                    'name' => $u->full_name,
                    'role' => $u->role?->name,
                ])
        );
    }

    /**
     * Bust all assignable-user cache variants. Call after user create/update.
     */
    public static function bustAssignableCache(): void
    {
        foreach (['all', 'lawyer', 'clerk'] as $role) {
            foreach ([50, 100, 200] as $limit) {
                Cache::forget("assignable_users_{$role}_{$limit}");
            }
        }
    }
}