<?php
// app/Http/Controllers/Admin/AuditLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = LoginLog::query()
            ->leftJoin('users', 'login_logs.user_id', '=', 'users.id')
            ->select(
                'login_logs.*',
                'users.full_name as user_name',
                'users.email as user_email',
                'users.id as related_user_id'
            )
            ->orderBy('login_logs.created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('login_logs.email_attempted', 'like', "%{$search}%")
                  ->orWhere('users.full_name', 'like', "%{$search}%")
                  ->orWhere('login_logs.ip_address', 'like', "%{$search}%");
            });
        }

        // Keep backward-compat with old 'email' param
        if (!$request->filled('search') && $request->filled('email')) {
            $search = $request->email;
            $query->where(function ($q) use ($search) {
                $q->where('login_logs.email_attempted', 'like', "%{$search}%")
                  ->orWhere('users.full_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('login_logs.status', $request->status);
        }

        if ($request->filled('action')) {
            $query->where('login_logs.action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('login_logs.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('login_logs.created_at', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 15);
        $logs = $query->paginate($perPage);

        $transformedLogs = collect($logs->items())->map(function ($log) {
            return [
                'id'              => $log->id,
                'user'            => $log->related_user_id ? [
                    'id'    => $log->related_user_id,
                    'name'  => $log->user_name,
                    'email' => $log->user_email,
                ] : null,
                'email_attempted' => $log->email_attempted,
                'action'          => $log->action,
                'status'          => $log->status,
                'ip_address'      => $log->ip_address,
                'user_agent'      => $log->user_agent,
                'details'         => $log->details,
                'created_at'      => $log->created_at,
            ];
        });

        $stats = [
            'total'           => LoginLog::count(),
            'success'         => LoginLog::where('status', 'success')->count(),
            'failed'          => LoginLog::where('status', 'failed')->count(),
            'passwordChanges' => LoginLog::where('action', 'password_change')->count(),
            'logins'          => LoginLog::where('action', 'login')->count(),
            'logouts'         => LoginLog::where('action', 'logout')->count(),
        ];

        return response()->json([
            'data'  => $transformedLogs,
            'meta'  => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
                'from'         => $logs->firstItem(),
                'to'           => $logs->lastItem(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Case activity logs with filters and pagination.
     */
    public function caseActivityLogs(Request $request)
    {
        $query = DB::table('case_activity_logs')
            ->leftJoin('users', 'case_activity_logs.user_id', '=', 'users.id')
            ->leftJoin('cases', 'case_activity_logs.case_id', '=', 'cases.id')
            ->select(
                'case_activity_logs.id',
                'case_activity_logs.case_id',
                'case_activity_logs.action',
                'case_activity_logs.details',
                'case_activity_logs.created_at',
                'users.id       as user_id',
                'users.full_name as actor_name',
                'cases.case_code',
                'cases.title    as case_title',
                'cases.case_no',
            )
            ->orderByDesc('case_activity_logs.created_at');

        // Search: actor name, case code, case title, action
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('users.full_name',             'like', $s)
                  ->orWhere('cases.case_code',           'like', $s)
                  ->orWhere('cases.title',               'like', $s)
                  ->orWhere('case_activity_logs.action', 'like', $s);
            });
        }

        if ($request->filled('action')) {
            $query->where('case_activity_logs.action', $request->action);
        }

        if ($request->filled('case_id')) {
            $query->where('case_activity_logs.case_id', (int) $request->case_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('case_activity_logs.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('case_activity_logs.created_at', '<=', $request->date_to);
        }

        $perPage = (int) $request->get('per_page', 15);
        $page    = (int) $request->get('page', 1);
        $total   = (clone $query)->count();
        $items   = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $transformed = $items->map(function ($log) {
            $details = null;
            if ($log->details) {
                $decoded = json_decode($log->details, true);
                $details = is_array($decoded) ? $decoded : ['note' => $log->details];
            }

            return [
                'id'         => $log->id,
                'case_id'    => $log->case_id,
                'case_code'  => $log->case_code,
                'case_title' => $log->case_title,
                'case_no'    => $log->case_no,
                'actor'      => $log->actor_name ?? 'System',
                'action'     => $log->action,
                'details'    => $details,
                'created_at' => $log->created_at,
            ];
        });

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
                'per_page'     => $perPage,
                'total'        => $total,
                'from'         => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    /**
     * Get distinct case actions for filter dropdown.
     */
    public function getCaseActions()
    {
        $actions = DB::table('case_activity_logs')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn ($a) => [
                'value' => $a,
                'label' => ucfirst(str_replace('_', ' ', $a)),
            ]);

        return response()->json($actions);
    }

    /**
     * Get a single audit log entry.
     */
    public function show($id)
    {
        $log = DB::table('login_logs')
            ->leftJoin('users', 'login_logs.user_id', '=', 'users.id')
            ->select(
                'login_logs.*',
                'users.full_name as user_name',
                'users.email as user_email',
                'users.id as related_user_id'
            )
            ->where('login_logs.id', $id)
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        return response()->json([
            'data' => [
                'id'              => $log->id,
                'user'            => $log->related_user_id ? [
                    'id'    => $log->related_user_id,
                    'name'  => $log->user_name,
                    'email' => $log->user_email,
                ] : null,
                'email_attempted' => $log->email_attempted,
                'action'          => $log->action,
                'status'          => $log->status,
                'ip_address'      => $log->ip_address,
                'user_agent'      => $log->user_agent,
                'details'         => $log->details,
                'created_at'      => $log->created_at,
            ],
        ]);
    }

    /**
     * Export audit logs as CSV.
     */
    public function export(Request $request)
    {
        $query = DB::table('login_logs')
            ->leftJoin('users', 'login_logs.user_id', '=', 'users.id')
            ->select('login_logs.*', 'users.full_name as user_name')
            ->orderBy('login_logs.created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('login_logs.email_attempted', 'like', "%{$search}%")
                  ->orWhere('users.full_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('login_logs.status', $request->status);
        }

        if ($request->filled('action')) {
            $query->where('login_logs.action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('login_logs.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('login_logs.created_at', '<=', $request->date_to);
        }

        $logs = $query->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID','User','Email Attempted','Action','Status','IP Address','User Agent','Details','Date & Time']);
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user_name ?? '—',
                    $log->email_attempted,
                    $log->action,
                    $log->status,
                    $log->ip_address,
                    $log->user_agent,
                    $log->details,
                    $log->created_at,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get distinct actions for filter dropdown.
     */
    public function getActions()
    {
        $actions = LoginLog::distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(function ($action) {
                return [
                    'value' => $action,
                    'label' => $this->formatActionLabel($action),
                ];
            });

        return response()->json($actions);
    }

    private function formatActionLabel($action)
    {
        $labels = [
            'login'               => 'Login',
            'logout'              => 'Logout',
            'password_change'     => 'Password Change',
            'user_create'         => 'User Created',
            'user_update'         => 'User Updated',
            'user_delete'         => 'User Deleted',
            'user_create_failed'  => 'User Creation Failed',
            'user_view'           => 'User Viewed',
            'activated'           => 'Account Activated',
            'deactivated'         => 'Account Deactivated',
        ];

        return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}