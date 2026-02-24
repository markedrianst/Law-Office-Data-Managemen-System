<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'         => 'nullable|string|max:100',
            'case_status'    => 'nullable|in:active,closed,archived',
            'intake_status'  => 'nullable|in:draft,for_approval,approved,returned',
            'priority'       => 'nullable|in:low,normal,urgent',
            'sort_by'        => 'nullable|in:case_code,title,priority,intake_status,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:5|max:100',
        ]);

        $sortColumn = match ($request->sort_by ?? 'created_at') {
            'case_no'       => 'cases.case_no',
            'case_code'     => 'cases.case_code',
            'title'         => 'cases.title',
            'priority'      => 'cases.priority',
            'intake_status' => 'cases.intake_status',
            default         => 'cases.created_at',
        };

        $query = DB::table('cases')
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->leftJoin('clients',         'cases.client_id',   '=', 'clients.id')
            ->leftJoin('users as lawyer', 'cases.assigned_lawyer_id', '=', 'lawyer.id')
            ->leftJoin('users as clerk',  'cases.assigned_clerk_id',  '=', 'clerk.id')
            ->select([
                'cases.id',
                'cases.case_no',
                'cases.case_code',
                'cases.title',
                'cases.court_or_office',
                'cases.docket_no',
                'cases.priority',
                'cases.intake_status',
                'cases.case_status',
                'cases.summary',
                'cases.category_id',
                'cases.client_id',
                'cases.assigned_lawyer_id',
                'cases.assigned_clerk_id',
                'cases.created_at',
                'cases.updated_at',
                'case_categories.name  as category_name',
                'clients.full_name     as client_name',
                'lawyer.full_name      as lawyer_name',
                'clerk.full_name       as clerk_name',
            ])
            ->orderBy($sortColumn, $request->sort_direction ?? 'desc');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('cases.case_code', 'like', $search)
                  ->orWhere('cases.title', 'like', $search)
                  ->orWhere('clients.full_name', 'like', $search);
            });
        }

        if ($request->filled('case_status'))   $query->where('cases.case_status',   $request->case_status);
        if ($request->filled('intake_status')) $query->where('cases.intake_status', $request->intake_status);
        if ($request->filled('priority'))      $query->where('cases.priority',      $request->priority);

        $perPage   = (int) ($request->per_page ?? 10);
        $page      = (int) ($request->page ?? 1);
        $total     = (clone $query)->count();
        $items     = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $case = DB::table('cases')
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->leftJoin('clients',         'cases.client_id',   '=', 'clients.id')
            ->leftJoin('users as lawyer', 'cases.assigned_lawyer_id', '=', 'lawyer.id')
            ->leftJoin('users as clerk',  'cases.assigned_clerk_id',  '=', 'clerk.id')
            ->select([
                'cases.id',
                'cases.case_no',           // ← ADD THIS LINE
                'cases.case_code',
                'cases.title',
                'cases.court_or_office',
                'cases.docket_no',
                'cases.priority',
                'cases.intake_status',
                'cases.case_status',
                'cases.summary',
                'cases.category_id',
                'cases.client_id',
                'cases.assigned_lawyer_id',
                'cases.assigned_clerk_id',
                'cases.created_at',
                'cases.updated_at',
                'case_categories.name  as category_name',
                'clients.full_name     as client_name',
                'lawyer.full_name      as lawyer_name',
                'clerk.full_name       as clerk_name',
            ])
            ->where('cases.id', $id)
            ->first();

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return response()->json(['data' => $case]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'case_no'            => 'required|string|max:200|:cases,case_no',
            'title'              => 'required|string|max:200',
            'category_id'        => 'nullable|exists:case_categories,id',
            'client_id'          => 'nullable|exists:clients,id',
            'court_or_office'    => 'nullable|string|max:180',
            'docket_no'          => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id'  => 'nullable|exists:users,id',
            'priority'           => 'required|in:low,normal,urgent',
            'intake_status'      => 'required|in:draft,for_approval,approved,returned',
            'case_status'        => 'required|in:active,closed,archived',
            'summary'            => 'nullable|string|max:2000',
        ]);

        $case = DB::transaction(function () use ($validated, $request) {
            $year  = date('Y');
            $count = DB::table('cases')
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->count();
            $seq = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $caseId = DB::table('cases')->insertGetId([
                'case_no'            => $validated['case_no'],
                'case_code'          => "{$year}-{$seq}",
                'title'              => $validated['title'],
                'category_id'        => $validated['category_id']        ?? null,
                'client_id'          => $validated['client_id']          ?? null,
                'court_or_office'    => $validated['court_or_office']    ?? null,
                'docket_no'          => $validated['docket_no']          ?? null,
                'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                'assigned_clerk_id'  => $validated['assigned_clerk_id']  ?? null,
                'priority'           => $validated['priority'],
                'intake_status'      => $validated['intake_status'],
                'case_status'        => $validated['case_status'],
                'summary'            => $validated['summary']            ?? null,
                'created_by'         => auth()->id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::table('case_activity_logs')->insert([
                'case_id'    => $caseId,
                'user_id'    => auth()->id(),
                'action'     => 'created the case',
                'details'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lawyer = DB::table('users')->where('id', $validated['assigned_lawyer_id'])->value('full_name');
            DB::table('case_activity_logs')->insert([
                'case_id'    => $caseId,
                'user_id'    => auth()->id(),
                'action'     => 'assigned lawyer',
                'details'    => $lawyer ?? "User ID #{$validated['assigned_lawyer_id']}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('cases')
                ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
                ->leftJoin('clients',         'cases.client_id',   '=', 'clients.id')
                ->leftJoin('users as lawyer', 'cases.assigned_lawyer_id', '=', 'lawyer.id')
                ->leftJoin('users as clerk',  'cases.assigned_clerk_id',  '=', 'clerk.id')
                ->select([
                    'cases.id','cases.case_no', 'cases.case_code', 'cases.title',
                    'cases.court_or_office', 'cases.docket_no',
                    'cases.priority', 'cases.intake_status', 'cases.case_status',
                    'cases.summary', 'cases.category_id', 'cases.client_id',
                    'cases.assigned_lawyer_id', 'cases.assigned_clerk_id',
                    'cases.created_at', 'cases.updated_at',
                    'case_categories.name as category_name',
                    'clients.full_name    as client_name',
                    'lawyer.full_name     as lawyer_name',
                    'clerk.full_name      as clerk_name',
                ])
                ->where('cases.id', $caseId)
                ->first();
        });

        return response()->json([
            'message' => 'Case created successfully.',
            'data'    => $case,
        ], 201);
    }

public function update(Request $request, int $id): JsonResponse
{
    // Find the existing case
    $case = DB::table('cases')->where('id', $id)->first();

    if (! $case) {
        return response()->json([
            'message' => 'Case not found.',
            'errors' => ['id' => ['No case found with ID: ' . $id]]
        ], 404);
    }

    // Validate the request
    $validated = $request->validate([
        'case_no'            => [
            'required',
            'string',
            'max:200',
            'unique:cases,case_no,' . $case->id . ',id'
        ],
        'title'              => 'required|string|max:200',
        'category_id'        => 'nullable|exists:case_categories,id',
        'client_id'          => 'nullable|exists:clients,id',
        'court_or_office'    => 'nullable|string|max:180',
        'docket_no'          => 'nullable|string|max:80',
        'assigned_lawyer_id' => 'required|exists:users,id',
        'assigned_clerk_id'  => 'nullable|exists:users,id',
        'priority'           => 'required|in:low,normal,urgent',
        'intake_status'      => 'required|in:draft,for_approval,approved,returned',
        'case_status'        => 'required|in:active,closed,archived',
        'summary'            => 'nullable|string|max:2000',
    ]);

    try {
        $updated = DB::transaction(function () use ($case, $validated) {
            // Track changes for activity log
            $watchFields = ['case_no', 'title', 'priority', 'intake_status', 'case_status', 'assigned_lawyer_id', 'assigned_clerk_id'];
            $changes = [];

            foreach ($watchFields as $field) {
                $oldValue = $case->$field ?? null;
                $newValue = $validated[$field] ?? null;
                
                // Handle different data types for comparison
                if ($oldValue != $newValue) {  // Using != for loose comparison to handle type differences
                    $changes[$field] = [
                        'from' => $oldValue,
                        'to' => $newValue
                    ];
                }
            }

            // Update the case
            DB::table('cases')->where('id', $case->id)->update([
                'case_no'            => $validated['case_no'],
                'title'              => $validated['title'],
                'category_id'        => $validated['category_id'] ?? null,
                'client_id'          => $validated['client_id'] ?? null,
                'court_or_office'    => $validated['court_or_office'] ?? null,
                'docket_no'          => $validated['docket_no'] ?? null,
                'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                'assigned_clerk_id'  => $validated['assigned_clerk_id'] ?? null,
                'priority'           => $validated['priority'],
                'intake_status'      => $validated['intake_status'],
                'case_status'        => $validated['case_status'],
                'summary'            => $validated['summary'] ?? null,
                'updated_at'         => now(),
            ]);

            // Log the changes
            if (!empty($changes)) {
                foreach ($changes as $field => $change) {
                    DB::table('case_activity_logs')->insert([
                        'case_id'    => $case->id,
                        'user_id'    => auth()->id(),
                        'action'     => "updated {$field}",
                        'details'    => json_encode([
                            'from' => $change['from'],
                            'to' => $change['to']
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('case_activity_logs')->insert([
                    'case_id'    => $case->id,
                    'user_id'    => auth()->id(),
                    'action'     => 'updated case details',
                    'details'    => json_encode(['note' => 'No significant changes detected']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Return the updated case with relationships
            return DB::table('cases')
                ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
                ->leftJoin('clients', 'cases.client_id', '=', 'clients.id')
                ->leftJoin('users as lawyer', 'cases.assigned_lawyer_id', '=', 'lawyer.id')
                ->leftJoin('users as clerk', 'cases.assigned_clerk_id', '=', 'clerk.id')
                ->select([
                    'cases.id',
                    'cases.case_no',
                    'cases.case_code',
                    'cases.title',
                    'cases.court_or_office',
                    'cases.docket_no',
                    'cases.priority',
                    'cases.intake_status',
                    'cases.case_status',
                    'cases.summary',
                    'cases.category_id',
                    'cases.client_id',
                    'cases.assigned_lawyer_id',
                    'cases.assigned_clerk_id',
                    'cases.created_at',
                    'cases.updated_at',
                    'case_categories.name as category_name',
                    'clients.full_name as client_name',
                    'lawyer.full_name as lawyer_name',
                    'clerk.full_name as clerk_name',
                ])
                ->where('cases.id', $case->id)
                ->first();
        });

        return response()->json([
            'message' => 'Case updated successfully.',
            'data' => $updated
        ], 200);

    } catch (\Illuminate\Database\QueryException $e) {
        // Handle database errors
        return response()->json([
            'message' => 'Database error occurred while updating case.',
            'error' => $e->getMessage()
        ], 500);
        
    } catch (\Exception $e) {
        // Handle general errors
        return response()->json([
            'message' => 'An unexpected error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function archive(int $id): JsonResponse
    {
        $case = DB::table('cases')->where('id', $id)->first();

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        DB::transaction(function () use ($case) {
            DB::table('cases')->where('id', $case->id)->update([
                'case_status' => 'archived',
                'updated_at'  => now(),
            ]);

            DB::table('case_activity_logs')->insert([
                'case_id'    => $case->id,
                'user_id'    => auth()->id(),
                'action'     => 'archived the case',
                'details'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Case archived successfully.']);
    }

    public function activityLogs(int $id): JsonResponse
    {
        $case = DB::table('cases')->where('id', $id)->first();

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        $logs = DB::table('case_activity_logs')
            ->leftJoin('users', 'case_activity_logs.user_id', '=', 'users.id')
            ->select([
                'users.full_name as actor',
                'case_activity_logs.action',
                'case_activity_logs.details as note',
                'case_activity_logs.created_at',
            ])
            ->where('case_activity_logs.case_id', $id)
            ->orderByDesc('case_activity_logs.created_at')
            ->get()
            ->map(fn ($log) => [
                'actor'  => $log->actor ?? 'System',
                'action' => $log->action,
                'note'   => $log->note,
                'time'   => \Carbon\Carbon::parse($log->created_at)->format('M d, Y g:i A'),
            ]);

        return response()->json(['data' => $logs]);
    }

    public function categories(): JsonResponse
    {
        $categories = DB::table('case_categories')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $categories]);
    }

    public function assignableUsers(): JsonResponse
    {
        $users = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['lawyer', 'clerk'])
            ->where('users.status', 'active')
            ->orderBy('users.full_name')
            ->get([
                'users.id',
                'users.full_name as name',
                'roles.name      as role',
            ]);

        return response()->json(['data' => $users]);
    }

    public function listClients(): JsonResponse
    {
        $clients = DB::table('clients')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'contact_no', 'email']);

        return response()->json(['data' => $clients]);
    }

    public function quickCreateClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|max:150',
            'contact_no' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100|unique:clients,email',
            'address'    => 'nullable|string|max:255',
        ]);

        $clientId = DB::table('clients')->insertGetId([
            'full_name'  => $validated['full_name'],
            'contact_no' => $validated['contact_no'] ?? null,
            'email'      => $validated['email']       ?? null,
            'address'    => $validated['address']     ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $client = DB::table('clients')->where('id', $clientId)->first();

        return response()->json([
            'message' => 'Client created successfully.',
            'data'    => $client,
        ], 201);
    }
}