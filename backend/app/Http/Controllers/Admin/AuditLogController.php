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

        // Filter by email or name
        if ($request->filled('email')) {
            $search = $request->email;
            $query->where(function ($q) use ($search) {
                $q->where('login_logs.email_attempted', 'like', "%{$search}%")
                  ->orWhere('users.full_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('login_logs.status', $request->status);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('login_logs.action', $request->action);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('login_logs.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('login_logs.created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $logs = $query->paginate($perPage);

        // Transform logs
        $transformedLogs = collect($logs->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->related_user_id ? [
                    'id' => $log->related_user_id,
                    'name' => $log->user_name,
                    'email' => $log->user_email
                ] : null,
                'email_attempted' => $log->email_attempted,
                'action' => $log->action,
                'status' => $log->status,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'details' => $log->details,
                'created_at' => $log->created_at
            ];
        });

        // Get statistics
        $stats = [
            'total' => LoginLog::count(),
            'success' => LoginLog::where('status', 'success')->count(),
            'failed' => LoginLog::where('status', 'failed')->count(),
            'passwordChanges' => LoginLog::where('action', 'password_change')->count(),
            'logins' => LoginLog::where('action', 'login')->count(),
            'logouts' => LoginLog::where('action', 'logout')->count()
        ];

        return response()->json([
            'data' => $transformedLogs,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem()
            ],
            'stats' => $stats
        ]);
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
                'id' => $log->id,
                'user' => $log->related_user_id ? [
                    'id' => $log->related_user_id,
                    'name' => $log->user_name,
                    'email' => $log->user_email
                ] : null,
                'email_attempted' => $log->email_attempted,
                'action' => $log->action,
                'status' => $log->status,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'details' => $log->details,
                'created_at' => $log->created_at
            ]
        ]);
    }

    /**
     * Export audit logs as CSV.
     */
    public function export(Request $request)
    {
        $query = DB::table('login_logs')
            ->leftJoin('users', 'login_logs.user_id', '=', 'users.id')
            ->select(
                'login_logs.*',
                'users.full_name as user_name'
            )
            ->orderBy('login_logs.created_at', 'desc');

        // Apply filters
        if ($request->filled('email')) {
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

        $logs = $query->get();

        // Generate CSV
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'User',
                'Email Attempted',
                'Action',
                'Status',
                'IP Address',
                'User Agent',
                'Details',
                'Date & Time'
            ]);

            // Data rows
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
                    $log->created_at
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
                    'label' => $this->formatActionLabel($action)
                ];
            });

        return response()->json($actions);
    }

    /**
     * Format action label for display.
     */
    private function formatActionLabel($action)
    {
        $labels = [
            'login' => 'Login',
            'logout' => 'Logout',
            'password_change' => 'Password Change',
            'user_create' => 'User Created',
            'user_update' => 'User Updated',
            'user_delete' => 'User Deleted',
            'user_create_failed' => 'User Creation Failed',
            'user_view' => 'User Viewed',
            'activated' => 'Account Activated',
            'deactivated' => 'Account Deactivated'
        ];

        return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}