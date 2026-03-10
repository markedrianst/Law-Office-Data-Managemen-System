<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Cases extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'case_no',
        'case_code',
        'title',
        'category_id',
        'client_id',
        'is_out',
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
        return $this->belongsTo(CaseStage::class, 'current_stage_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CaseActivityLog::class, 'case_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(CaseStageHistory::class, 'case_id');
    }

        public function checklists(): HasMany
    {
        return $this->hasMany(CaseChecklist::class, 'case_id');
    }

    public function folderMovements(): HasMany
    {
        return $this->hasMany(FolderMovement::class, 'case_id');
    }

    public function checklistMovements(): HasMany
    {
        return $this->hasMany(ChecklistMovement::class, 'case_id');
    }

    // ── SELECT column lists ───────────────────────────────────────────────────

    /**
     * Columns for the paginated list and show() — includes all FK ids and
     * joined name aliases.
     */
    public static function listColumns(): array
    {
        return [
            'cases.id',
            'cases.case_no',
            'cases.case_code',
            'cases.title',
            'cases.priority',
            'cases.case_status',
            'cases.current_stage_id',
            'cases.category_id',
            'cases.client_id',
            'cases.assigned_lawyer_id',
            'cases.assigned_clerk_id',
            'cases.court_or_office',
            'cases.docket_no',
            'cases.summary',
            'cases.is_out',
            'cases.created_at',
            'cases.updated_at',
            'cc.name      AS category_name',
            'cl.full_name AS client_name',
            'lw.full_name AS lawyer_name',
            'ck.full_name AS clerk_name',
            'cs.name      AS stage_name',
        ];
    }

    /**
     * Columns for export — strips IDs/FKs, keeps human-readable fields only.
     */
    public static function exportColumns(): array
    {
        return [
            'cases.case_no',
            'cases.case_code',
            'cases.title',
            'cases.priority',
            'cases.case_status',
            'cases.court_or_office',
            'cases.docket_no',
            'cases.summary',
            'cases.created_at',
            'cc.name      AS category_name',
            'cl.full_name AS client_name',
            'lw.full_name AS lawyer_name',
            'ck.full_name AS clerk_name',
            'cs.name      AS stage_name',
        ];
    }

    // ── Query Scopes ──────────────────────────────────────────────────────────

    /**
     * Applies the five standard LEFT JOINs and selects the given columns.
     * Used by: index(), show(), export(), fetchFormattedCase().
     */
    public function scopeWithJoins($query, array $columns)
    {
        return $query->select($columns)
            ->leftJoin('case_categories AS cc', 'cc.id', '=', 'cases.category_id')
            ->leftJoin('clients         AS cl', 'cl.id', '=', 'cases.client_id')
            ->leftJoin('users           AS lw', 'lw.id', '=', 'cases.assigned_lawyer_id')
            ->leftJoin('users           AS ck', 'ck.id', '=', 'cases.assigned_clerk_id')
            ->leftJoin('case_stages     AS cs', 'cs.id', '=', 'cases.current_stage_id');
    }

    /**
     * Filter by case_status.
     */
    public function scopeOfStatus($query, ?string $status)
    {
        return $status ? $query->where('cases.case_status', $status) : $query;
    }

    /**
     * Filter by priority.
     */
    public function scopeOfPriority($query, ?string $priority)
    {
        return $priority ? $query->where('cases.priority', $priority) : $query;
    }

    /**
     * Filter by current_stage_id.
     */
    public function scopeOfStage($query, ?int $stageId)
    {
        return $stageId ? $query->where('cases.current_stage_id', $stageId) : $query;
    }

    /**
     * Full-text search across case_code, title, and client name.
     * Requires the clients join — scopeWithJoins() covers it.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('cases.case_code', 'like', $like)
              ->orWhere('cases.title',   'like', $like)
              ->orWhere('cl.full_name',  'like', $like);
        });
    }

    /**
     * Maps sort_by param to a qualified column name.
     */
    public static function resolveSortColumn(string $sortBy): string
    {
        return match ($sortBy) {
            'case_no'   => 'cases.case_no',
            'case_code' => 'cases.case_code',
            'title'     => 'cases.title',
            'priority'  => 'cases.priority',
            default     => 'cases.created_at',
        };
    }

    /**
     * Lightweight COUNT(*) with the same filters as the list query.
     * Only joins clients (needed for the search filter) to keep it fast.
     */
    public static function filteredCount(
        ?string $status,
        ?string $priority,
        ?int    $stageId,
        ?string $search,
        ?int    $clerkId,
        ?int    $lawyerId
    ): int {
        $q = DB::table('cases')
            ->leftJoin('clients AS cl', 'cl.id', '=', 'cases.client_id');

        if ($status)   $q->where('cases.case_status',       $status);
        if ($priority) $q->where('cases.priority',          $priority);
        if ($stageId)  $q->where('cases.current_stage_id',  $stageId);
        if ($clerkId)  $q->where('cases.assigned_clerk_id', $clerkId);
        if ($lawyerId) $q->where('cases.assigned_lawyer_id', $lawyerId);

        if ($search) {
            $like = '%' . $search . '%';
            $q->where(fn($x) => $x
                ->where('cases.case_code', 'like', $like)
                ->orWhere('cases.title',   'like', $like)
                ->orWhere('cl.full_name',  'like', $like)
            );
        }

        return $q->count();
    }

    /**
     * Next zero-padded sequence number for the given year.
     * Must be called inside a DB transaction (uses lockForUpdate).
     */
    public static function nextSequence(int $year): string
    {
        $count = static::whereYear('created_at', $year)->lockForUpdate()->count();

        return str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Formats a flat stdClass row (from a join query) into the standard
     * response array shape used by the API.
     */
    public static function formatRow(object $c): array
    {
        return [
            'id'                 => $c->id,
            'case_no'            => $c->case_no,
            'case_code'          => $c->case_code,
            'title'              => $c->title,
            'court_or_office'    => $c->court_or_office,
            'docket_no'          => $c->docket_no,
            'priority'           => $c->priority,
            'case_status'        => $c->case_status,
            'current_stage_id'   => $c->current_stage_id,
            'summary'            => $c->summary,
            'category_id'        => $c->category_id,
            'client_id'          => $c->client_id,
            'assigned_lawyer_id' => $c->assigned_lawyer_id,
            'assigned_clerk_id'  => $c->assigned_clerk_id,
            'is_out'             => $c->is_out ?? 0,
            'created_at'         => $c->created_at,
            'updated_at'         => $c->updated_at,
            'category_name'      => $c->category_name ?? null,
            'client_name'        => $c->client_name   ?? null,
            'lawyer_name'        => $c->lawyer_name   ?? null,
            'clerk_name'         => $c->clerk_name    ?? null,
            'stage_name'         => $c->stage_name    ?? null,
        ];
    }
}