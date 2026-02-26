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

class CaseController extends Controller
{
    // =========================================================================
    // HELPER — flat array the frontend already expects
    // =========================================================================
    private function formatCase(Cases $case): array
    {
        return [
            'id'                 => $case->id,
            'case_no'            => $case->case_no,
            'case_code'          => $case->case_code,
            'title'              => $case->title,
            'court_or_office'    => $case->court_or_office,
            'docket_no'          => $case->docket_no,
            'priority'           => $case->priority,
            'case_status'        => $case->case_status,
            'current_stage_id'   => $case->current_stage_id,
            'summary'            => $case->summary,
            'category_id'        => $case->category_id,
            'client_id'          => $case->client_id,
            'assigned_lawyer_id' => $case->assigned_lawyer_id,
            'assigned_clerk_id'  => $case->assigned_clerk_id,
            'created_at'         => $case->created_at,
            'updated_at'         => $case->updated_at,
            // joined display fields
            'category_name'      => $case->category?->name,
            'client_name'        => $case->client?->full_name,
            'lawyer_name'        => $case->lawyer?->full_name,
            'clerk_name'         => $case->clerk?->full_name,
            'stage_name'         => $case->currentStage?->name,
        ];
    }

    // ── eager-load used in every read ────────────────────────────────────────
    private function withRelations(): array
    {
        return ['category', 'client', 'lawyer', 'clerk', 'currentStage'];
    }

    // =========================================================================
    // GET /admin/cases
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
            'case_no'   => 'case_no',
            'case_code' => 'case_code',
            'title'     => 'title',
            'priority'  => 'priority',
            default     => 'created_at',
        };

        $query = Cases::with($this->withRelations())
            ->orderBy($sortColumn, $request->sort_direction ?? 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('case_code', 'like', "%{$search}%")
                  ->orWhere('title',   'like', "%{$search}%")
                  ->orWhereHas('client', fn ($q) =>
                        $q->where('full_name', 'like', "%{$search}%")
                  );
            });
        }

        if ($request->filled('case_status')) $query->where('case_status',     $request->case_status);
        if ($request->filled('priority'))    $query->where('priority',         $request->priority);
        if ($request->filled('stage_id'))    $query->where('current_stage_id', $request->stage_id);

        $perPage = (int) ($request->per_page ?? 10);
        $results = $query->paginate($perPage, ['*'], 'page', $request->page ?? 1);

        return response()->json([
            'data' => $results->map(fn ($c) => $this->formatCase($c)),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    // =========================================================================
    // GET /admin/cases/{id}
    // =========================================================================
    public function show(int $id): JsonResponse
    {
        $case = Cases::with($this->withRelations())->find($id);

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return response()->json(['data' => $this->formatCase($case)]);
    }

    // =========================================================================
    // POST /admin/cases
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'case_no'            => 'required|string|max:180|unique:cases,case_no',
            'title'              => 'required|string|max:200',
            'category_id'        => 'nullable|exists:case_categories,id',
            'client_id'          => 'nullable|exists:clients,id',
            'court_or_office'    => 'nullable|string|max:180',
            'docket_no'          => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id'  => 'nullable|exists:users,id',
            'priority'           => 'required|in:low,normal,urgent',
            'case_status'        => 'required|in:active,closed,archived',
            'current_stage_id'   => 'nullable|integer|exists:case_stages,id',
            'summary'            => 'nullable|string|max:2000',
        ]);

        $case = DB::transaction(function () use ($validated) {
            $year    = date('Y');
            $count   = Cases::whereYear('created_at', $year)->lockForUpdate()->count();
            $seq     = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $actorId = auth()->id();

            // Fall back to first active stage if none provided
            $stageId = ! empty($validated['current_stage_id'])
                ? (int) $validated['current_stage_id']
                : CaseStage::where('is_active', true)->orderBy('id')->value('id');

            /** @var Cases $case */
            $case = Cases::create([
                'case_no'            => $validated['case_no'],
                'case_code'          => "{$year}-{$seq}",
                'title'              => $validated['title'],
                'category_id'        => $validated['category_id']       ?? null,
                'client_id'          => $validated['client_id']         ?? null,
                'court_or_office'    => $validated['court_or_office']   ?? null,
                'docket_no'          => $validated['docket_no']         ?? null,
                'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                'assigned_clerk_id'  => $validated['assigned_clerk_id'] ?? null,
                'priority'           => $validated['priority'],
                'case_status'        => $validated['case_status'],
                'current_stage_id'   => $stageId,
                'summary'            => $validated['summary']           ?? null,
                'created_by'         => $actorId,
            ]);

            // Initial stage history
            if ($stageId) {
                DB::table('case_stage_histories')->insert([
                    'case_id'       => $case->id,
                    'from_stage_id' => null,
                    'to_stage_id'   => $stageId,
                    'changed_by'    => $actorId,
                    'remarks'       => 'Initial stage set on case creation',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            // Activity logs
            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => $actorId,
                'action'  => 'created the case',
                'details' => null,
            ]);

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => $actorId,
                'action'  => 'assigned lawyer',
                'details' => User::find($validated['assigned_lawyer_id'])?->full_name
                             ?? "User ID #{$validated['assigned_lawyer_id']}",
            ]);

            return $case->load($this->withRelations());
        });

        return response()->json([
            'message' => 'Case created successfully.',
            'data'    => $this->formatCase($case),
        ], 201);
    }

    // =========================================================================
    // PUT /admin/cases/{id}
    // =========================================================================
    public function update(Request $request, int $id): JsonResponse
    {
        $case = Cases::find($id);

        if (! $case) {
            return response()->json([
                'message' => 'Case not found.',
                'errors'  => ['id' => ["No case found with ID: {$id}"]],
            ], 404);
        }

        $validated = $request->validate([
            'case_no'            => ['required', 'string', 'max:180', "unique:cases,case_no,{$case->id},id"],
            'title'              => 'required|string|max:200',
            'category_id'        => 'nullable|exists:case_categories,id',
            'client_id'          => 'nullable|exists:clients,id',
            'court_or_office'    => 'nullable|string|max:180',
            'docket_no'          => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id'  => 'nullable|exists:users,id',
            'priority'           => 'required|in:low,normal,urgent',
            'case_status'        => 'required|in:active,closed,archived',
            'current_stage_id'   => 'nullable|integer|exists:case_stages,id',
            'summary'            => 'nullable|string|max:2000',
        ]);

        try {
            $updated = DB::transaction(function () use ($case, $validated) {
                $actorId     = auth()->id();
                $watchFields = ['case_no', 'title', 'priority', 'case_status', 'assigned_lawyer_id', 'assigned_clerk_id'];
                $changes     = [];

                foreach ($watchFields as $field) {
                    $old = $case->$field ?? null;
                    $new = $validated[$field] ?? null;
                    if ($old != $new) {
                        $changes[$field] = ['from' => $old, 'to' => $new];
                    }
                }

                $oldStageId = (int) $case->current_stage_id;
                $newStageId = ! empty($validated['current_stage_id'])
                    ? (int) $validated['current_stage_id']
                    : $oldStageId;

                $case->update([
                    'case_no'            => $validated['case_no'],
                    'title'              => $validated['title'],
                    'category_id'        => $validated['category_id']       ?? null,
                    'client_id'          => $validated['client_id']         ?? null,
                    'court_or_office'    => $validated['court_or_office']   ?? null,
                    'docket_no'          => $validated['docket_no']         ?? null,
                    'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                    'assigned_clerk_id'  => $validated['assigned_clerk_id'] ?? null,
                    'priority'           => $validated['priority'],
                    'case_status'        => $validated['case_status'],
                    'current_stage_id'   => $newStageId,
                    'summary'            => $validated['summary']           ?? null,
                ]);

                // Stage history — only when stage actually changed
                if ($newStageId !== $oldStageId) {
                    DB::table('case_stage_histories')->insert([
                        'case_id'       => $case->id,
                        'from_stage_id' => $oldStageId ?: null,
                        'to_stage_id'   => $newStageId,
                        'changed_by'    => $actorId,
                        'remarks'       => 'Stage updated via case edit',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    $changes['current_stage_id'] = ['from' => $oldStageId, 'to' => $newStageId];
                }

                // Activity log — one row per changed field, or a "no change" row
                if (! empty($changes)) {
                    foreach ($changes as $field => $change) {
                        CaseActivityLog::create([
                            'case_id' => $case->id,
                            'user_id' => $actorId,
                            'action'  => "updated {$field}",
                            'details' => json_encode($change),
                        ]);
                    }
                } else {
                    CaseActivityLog::create([
                        'case_id' => $case->id,
                        'user_id' => $actorId,
                        'action'  => 'updated case details',
                        'details' => json_encode(['note' => 'No significant changes detected']),
                    ]);
                }

                return $case->load($this->withRelations());
            });

            return response()->json([
                'message' => 'Case updated successfully.',
                'data'    => $this->formatCase($updated),
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Database error.',   'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unexpected error.', 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PATCH /admin/cases/{id}/archive
    // =========================================================================
    public function archive(int $id): JsonResponse
    {
        $case = Cases::find($id);

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        DB::transaction(function () use ($case) {
            $case->update(['case_status' => 'archived']);

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => auth()->id(),
                'action'  => 'archived the case',
                'details' => null,
            ]);
        });

        return response()->json(['message' => 'Case archived successfully.']);
    }

    // =========================================================================
    // GET /admin/cases/{id}/activity-logs
    // =========================================================================
    public function activityLogs(int $id): JsonResponse
    {
        if (! Cases::where('id', $id)->exists()) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        $logs = CaseActivityLog::with('user')
            ->where('case_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($log) => [
                'actor'  => $log->user?->full_name ?? 'System',
                'action' => $log->action,
                'note'   => $log->details,
                'time'   => Carbon::parse($log->created_at)->format('M d, Y g:i A'),
            ]);

        return response()->json(['data' => $logs]);
    }

    // =========================================================================
    // LOOKUP ENDPOINTS
    // =========================================================================

    // GET /admin/cases/categories
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => CaseCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // GET /admin/cases/assignable-users
    public function assignableUsers(): JsonResponse
    {
        $users = User::with('role')
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['lawyer', 'clerk']))
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'role_id'])
            ->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->full_name,
                'role' => $u->role?->name,
            ]);

        return response()->json(['data' => $users]);
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
            'data' => $query->orderBy('name')->get(['id', 'name', 'type', 'is_active']),
        ]);
    }

    // GET /admin/cases/clients
    public function listClients(): JsonResponse
    {
        return response()->json([
            'data' => Client::orderBy('full_name')->get(['id', 'full_name', 'contact_no', 'email']),
        ]);
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
}