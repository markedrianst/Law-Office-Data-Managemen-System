<?php

namespace App\Http\Controllers\Admin\CaseMaster;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\CaseActivityLog;
use App\Models\CaseCategory;
use App\Models\CaseStage;
use App\Models\Client;
use App\Models\CourtOffice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CaseController extends Controller
{
    // =========================================================================
    // GET /admin/cases - ULTRA OPTIMIZED
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'         => 'nullable|string|max:100',
            'case_status'    => 'nullable|in:active,closed,archived',
            'priority'       => 'nullable|in:low,normal,urgent',
            'stage_id'       => 'nullable|integer|exists:case_stages,id',
            'sort_by'        => 'nullable|in:case_no,case_code,title,priority,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:5|max:100',
        ]);

        $sortColumn = match ($request->sort_by ?? 'created_at') {
            'case_no'   => 'cases.case_no',
            'case_code' => 'cases.case_code',
            'title'     => 'cases.title',
            'priority'  => 'cases.priority',
            default     => 'cases.created_at',
        };
        $sortDir = $request->sort_direction ?? 'desc';
        $perPage = (int) ($request->per_page ?? 10);
        $page    = (int) ($request->page    ?? 1);

        // ONE query with LEFT JOINs — eliminates 5 separate eager-load queries.
        // This is the biggest speed win: instead of 1 query to get cases +
        // 5 queries to resolve category/client/lawyer/clerk/stage, we do it all
        // in a single SQL with indexed FK joins.
        $query = DB::table('cases')
            ->select([
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
                'cases.created_at',
                'cases.updated_at',
                // Resolved names in one pass — no extra queries
                'cc.name AS category_name',
                'cl.full_name AS client_name',
                'lw.full_name AS lawyer_name',
                'ck.full_name AS clerk_name',
                'cs.name AS stage_name',
            ])
            ->leftJoin('case_categories AS cc', 'cc.id', '=', 'cases.category_id')
            ->leftJoin('clients         AS cl', 'cl.id', '=', 'cases.client_id')
            ->leftJoin('users           AS lw', 'lw.id', '=', 'cases.assigned_lawyer_id')
            ->leftJoin('users           AS ck', 'ck.id', '=', 'cases.assigned_clerk_id')
            ->leftJoin('case_stages     AS cs', 'cs.id', '=', 'cases.current_stage_id');

        // Filters
        if ($request->filled('case_status')) {
            $query->where('cases.case_status', $request->case_status);
        }
        if ($request->filled('priority')) {
            $query->where('cases.priority', $request->priority);
        }
        if ($request->filled('stage_id')) {
            $query->where('cases.current_stage_id', $request->stage_id);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cases.case_code', 'like', $term)
                  ->orWhere('cases.title',   'like', $term)
                  ->orWhere('cl.full_name',  'like', $term);
            });
        }

        // Count — cached 60s, keyed on filters only.
        // Re-build a lean count query instead of cloning the heavy SELECT+JOIN
        // query, which would wrap everything in a subquery.
        $filterKey = md5(serialize($request->only(['search','case_status','priority','stage_id'])));
        $total = Cache::remember("cases_count_{$filterKey}", 60, function () use ($request) {
            $countQ = DB::table('cases')
                ->leftJoin('clients AS cl', 'cl.id', '=', 'cases.client_id');
            if ($request->filled('case_status')) $countQ->where('cases.case_status', $request->case_status);
            if ($request->filled('priority'))    $countQ->where('cases.priority',    $request->priority);
            if ($request->filled('stage_id'))    $countQ->where('cases.current_stage_id', $request->stage_id);
            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $countQ->where(fn($q) => $q->where('cases.case_code', 'like', $term)
                    ->orWhere('cases.title',  'like', $term)
                    ->orWhere('cl.full_name', 'like', $term));
            }
            return $countQ->count();
        });

        $rows = $query
            ->orderBy($sortColumn, $sortDir)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data' => $this->formatRaw($rows),
            'meta' => [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }


    public function show(int|string $id): JsonResponse
    {
        $id = (int) $id;
        
        // OPTIMIZATION: Use cache for single case if needed
        $cacheKey = "case_{$id}";
        $case = Cache::remember($cacheKey, 60, function () use ($id) {
            return Cases::with([
                'category:id,name',
                'client:id,full_name',
                'lawyer:id,full_name',
                'clerk:id,full_name',
                'currentStage:id,name',
            ])->find($id);
        });

        if (!$case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return response()->json(['data' => $this->formatCasesRaw([$case])[0]]);
    }

    // =========================================================================
    // LOOKUP ENDPOINTS - WITH CACHING
    // =========================================================================

    // GET /admin/cases/categories - OPTIMIZED
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('case_categories', 300, function () {
            return CaseCategory::orderBy('name')
                ->get(['id', 'name']);
        });

        return response()->json(['data' => $categories]);
    }

    // GET /admin/cases/assignable-users - OPTIMIZED
    public function assignableUsers(Request $request): JsonResponse
    {
        $role = $request->input('role');
        $limit = (int) $request->input('limit', 100);
        
        // Create cache key based on parameters
        $cacheKey = 'assignable_users_' . ($role ?? 'all') . "_$limit";
        
        $users = Cache::remember($cacheKey, 300, function () use ($role, $limit) {
            $query = User::where('status', 'active')
                ->select('id', 'full_name', 'role_id')
                ->orderBy('full_name');

            if ($role && in_array($role, ['lawyer', 'clerk'])) {
                $query->whereHas('role', fn($q) => $q->where('name', $role));
            } else {
                $query->whereHas('role', fn($q) => $q->whereIn('name', ['lawyer', 'clerk']));
            }

            return $query->limit($limit)
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->full_name,
                    'role' => $u->role?->name,
                ]);
        });

        return response()->json(['data' => $users]);
    }

    // GET /admin/cases/stages - NEW OPTIMIZED ENDPOINT
    public function stages(Request $request): JsonResponse
    {
        $fields = $request->input('fields', 'id,name,is_active');
        $fieldArray = explode(',', $fields);
        
        $stages = Cache::remember('case_stages', 300, function () use ($fieldArray) {
            return CaseStage::orderBy('name')
                ->get($fieldArray);
        });

        return response()->json(['data' => $stages]);
    }

    // GET /admin/cases/clients - OPTIMIZED
    public function listClients(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $limit = (int) $request->input('limit', 50);
        $fields = $request->input('fields', 'id,full_name,contact_no,email');
        $fieldArray = explode(',', $fields);

        $cacheKey = 'clients_' . md5($search . $limit . implode(',', $fieldArray));
        
        $clients = Cache::remember($cacheKey, 60, function () use ($search, $limit, $fieldArray) {
            $query = Client::orderBy('full_name');

            if ($search) {
                $query->where('full_name', 'like', "%{$search}%");
            }

            return $query->select($fieldArray)
                ->limit($limit)
                ->get();
        });

        return response()->json(['data' => $clients]);
    }

    // =========================================================================
    // OTHER METHODS (unchanged but optimized)
    // =========================================================================

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'case_no' => 'required|string|max:180|unique:cases,case_no',
            'title' => 'required|string|max:200',
            'category_id' => 'nullable|exists:case_categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'court_or_office' => 'nullable|string|max:180',
            'docket_no' => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,normal,urgent',
            'case_status' => 'required|in:active,closed,archived',
            'current_stage_id' => 'nullable|integer|exists:case_stages,id',
            'summary' => 'nullable|string|max:2000',
        ]);

        $case = DB::transaction(function () use ($validated) {
            $year = date('Y');
            $count = Cases::whereYear('created_at', $year)->lockForUpdate()->count();
            $seq = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $actorId = auth()->id();

            $stageId = !empty($validated['current_stage_id'])
                ? (int) $validated['current_stage_id']
                : CaseStage::where('is_active', true)->orderBy('id')->value('id');

            $case = Cases::create([
                'case_no' => $validated['case_no'],
                'case_code' => "{$year}-{$seq}",
                'title' => $validated['title'],
                'category_id' => $validated['category_id'] ?? null,
                'client_id' => $validated['client_id'] ?? null,
                'court_or_office' => $validated['court_or_office'] ?? null,
                'docket_no' => $validated['docket_no'] ?? null,
                'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                'assigned_clerk_id' => $validated['assigned_clerk_id'] ?? null,
                'priority' => $validated['priority'],
                'case_status' => $validated['case_status'],
                'current_stage_id' => $stageId,
                'summary' => $validated['summary'] ?? null,
                'created_by' => $actorId,
            ]);

            if ($stageId) {
                DB::table('case_stage_histories')->insert([
                    'case_id' => $case->id,
                    'from_stage_id' => null,
                    'to_stage_id' => $stageId,
                    'changed_by' => $actorId,
                    'remarks' => 'Initial stage set on case creation',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => $actorId,
                'action' => 'created the case',
                'details' => null,
            ]);

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => $actorId,
                'action' => 'assigned lawyer',
                'details' => User::find($validated['assigned_lawyer_id'])?->full_name
                    ?? "User ID #{$validated['assigned_lawyer_id']}",
            ]);

            // Clear cache after mutation
            $this->clearCaseCache($case->id);

            return $case->load($this->withRelations());
        });

        return response()->json([
            'message' => 'Case created successfully.',
            'data' => $this->formatCasesRaw([$case])[0],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $case = Cases::find($id);

        if (!$case) {
            return response()->json([
                'message' => 'Case not found.',
                'errors' => ['id' => ["No case found with ID: {$id}"]],
            ], 404);
        }

        $validated = $request->validate([
            'case_no' => ['required', 'string', 'max:180', "unique:cases,case_no,{$case->id},id"],
            'title' => 'required|string|max:200',
            'category_id' => 'nullable|exists:case_categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'court_or_office' => 'nullable|string|max:180',
            'docket_no' => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,normal,urgent',
            'case_status' => 'required|in:active,closed,archived',
            'current_stage_id' => 'nullable|integer|exists:case_stages,id',
            'summary' => 'nullable|string|max:2000',
        ]);

        try {
            $updated = DB::transaction(function () use ($case, $validated) {
                $actorId = auth()->id();
                $watchFields = ['case_no', 'title', 'priority', 'case_status', 'assigned_lawyer_id', 'assigned_clerk_id'];
                $changes = [];

                foreach ($watchFields as $field) {
                    $old = $case->$field ?? null;
                    $new = $validated[$field] ?? null;
                    if ($old != $new) {
                        $changes[$field] = ['from' => $old, 'to' => $new];
                    }
                }

                $oldStageId = (int) $case->current_stage_id;
                $newStageId = !empty($validated['current_stage_id'])
                    ? (int) $validated['current_stage_id']
                    : $oldStageId;

                $case->update([
                    'case_no' => $validated['case_no'],
                    'title' => $validated['title'],
                    'category_id' => $validated['category_id'] ?? null,
                    'client_id' => $validated['client_id'] ?? null,
                    'court_or_office' => $validated['court_or_office'] ?? null,
                    'docket_no' => $validated['docket_no'] ?? null,
                    'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                    'assigned_clerk_id' => $validated['assigned_clerk_id'] ?? null,
                    'priority' => $validated['priority'],
                    'case_status' => $validated['case_status'],
                    'current_stage_id' => $newStageId,
                    'summary' => $validated['summary'] ?? null,
                ]);

                if ($newStageId !== $oldStageId) {
                    DB::table('case_stage_histories')->insert([
                        'case_id' => $case->id,
                        'from_stage_id' => $oldStageId ?: null,
                        'to_stage_id' => $newStageId,
                        'changed_by' => $actorId,
                        'remarks' => 'Stage updated via case edit',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $changes['current_stage_id'] = ['from' => $oldStageId, 'to' => $newStageId];
                }

                if (!empty($changes)) {
                    foreach ($changes as $field => $change) {
                        CaseActivityLog::create([
                            'case_id' => $case->id,
                            'user_id' => $actorId,
                            'action' => "updated {$field}",
                            'details' => json_encode($change),
                        ]);
                    }
                } else {
                    CaseActivityLog::create([
                        'case_id' => $case->id,
                        'user_id' => $actorId,
                        'action' => 'updated case details',
                        'details' => json_encode(['note' => 'No significant changes detected']),
                    ]);
                }

                // Clear cache after mutation
                $this->clearCaseCache($case->id);

                return $case->load($this->withRelations());
            });

            return response()->json([
                'message' => 'Case updated successfully.',
                'data' => $this->formatCasesRaw([$updated])[0],
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating case.'], 500);
        }
    }

    public function archive(int $id): JsonResponse
    {
        $case = Cases::find($id);

        if (!$case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        DB::transaction(function () use ($case) {
            $case->update(['case_status' => 'archived']);

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => auth()->id(),
                'action' => 'archived the case',
                'details' => null,
            ]);

            $this->clearCaseCache($case->id);
        });

        return response()->json(['message' => 'Case archived successfully.']);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Formatter — works for both DB::table() stdClass rows (index)
     * and Eloquent models with eager-loaded relations (show/store/update).
     */
    private function formatRaw($cases): array
    {
        $result = [];
        foreach ($cases as $c) {
            $isEloquent = $c instanceof \Illuminate\Database\Eloquent\Model;
            $result[] = [
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
                'created_at'         => $c->created_at,
                'updated_at'         => $c->updated_at,
                'category_name' => $isEloquent ? $c->category?->name     : $c->category_name,
                'client_name'   => $isEloquent ? $c->client?->full_name  : $c->client_name,
                'lawyer_name'   => $isEloquent ? $c->lawyer?->full_name  : $c->lawyer_name,
                'clerk_name'    => $isEloquent ? $c->clerk?->full_name   : $c->clerk_name,
                'stage_name'    => $isEloquent ? $c->currentStage?->name : $c->stage_name,
            ];
        }
        return $result;
    }

    // Alias so store/update/archive/show still work unchanged
    private function formatCasesRaw($cases): array { return $this->formatRaw($cases); }

    private function withRelations(): array
    {
        return ['category', 'client', 'lawyer', 'clerk', 'currentStage'];
    }

    private function clearCaseCache(int $caseId): void
    {
        Cache::forget("case_{$caseId}");
        Cache::forget('cases_total_count');
        // Don't clear all - just specific keys
    }

     // POST /admin/cases/clients/quick-create
    public function quickCreateClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|max:150',
            'contact_no' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100|unique:clients,email',
            'address'    => 'nullable|string|max:255',
        ]);

        $client = Client::create($validated);

        return response()->json([
            'message' => 'Client created successfully.',
            'data'    => $client,
        ], 201);
    }
     // GET /admin/cases/courts-offices
public function courtsOffices(Request $request): JsonResponse
{
    $query = CourtOffice::active();

    if ($request->filled('search')) {
        $query->search($request->search);
    }

    if ($request->filled('type')) {
        $query->ofType($request->type);
    }

    return response()->json([
        'data' => $query
            ->orderBy('sort_order', 'asc') // ✅ custom order column
            ->orderBy('name', 'asc')       // optional secondary sort
            ->get(['id', 'name', 'type', 'is_active', 'sort_order']),
    ]);
}

}