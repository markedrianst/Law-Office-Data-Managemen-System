<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        // Filter by role (Lawyer/Clerk only)
        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', strtolower($request->role));
            });
        } else {
            $query->whereHas('role', function ($q) {
                $q->whereIn('name', ['lawyer', 'clerk']);
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting - DEFAULT: created_at (newest first)
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $fieldMap = [
            'name'       => 'full_name',
            'email'      => 'email',
            'role'       => 'role_id',
            'status'     => 'status',
            'last_login' => 'last_login',
            'created_at' => 'created_at',
        ];
        
        $dbField = $fieldMap[$sortField] ?? 'created_at';
        $query->orderBy($dbField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users   = $query->paginate($perPage);

        $transformedUsers = $users->map(function ($user) {
            return [
                'id'             => $user->id,
                'name'           => $user->full_name,
                'email'          => $user->email,
                'role'           => ucfirst($user->role->name),
                'status'         => ucfirst($user->status),
                'created_at'     => $user->created_at,
                'last_login'     => $user->last_login,
                'address'        => $user->address ?? '',
                'contact_number' => $user->contact_number ?? '',
            ];
        });

        return response()->json([
            'data' => $transformedUsers,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users',
            'password'       => ['required', 'string', Password::min(6)],
            'role'           => 'required|in:Lawyer,Clerk',
            'address'        => 'nullable|string',
            'contact_number' => 'nullable|string|max:20',
            'status'         => 'sometimes|in:Active,Inactive',
        ]);

        $roleName = strtolower($validated['role']);
        $roleId   = \DB::table('roles')->where('name', $roleName)->value('id');

        if (!$roleId) {
            LoginLog::create([
                'user_id'          => $request->user()?->id,
                'action'           => 'user_create_failed',
                'email_attempted'  => $validated['email'],
                'ip_address'       => $request->ip(),
                'user_agent'       => $request->userAgent(),
                'status'           => 'failed',
                'details'          => 'Attempted to create user with invalid role: ' . $validated['role'],
            ]);

            return response()->json(['message' => 'Invalid role selected'], 422);
        }

        $userData = [
            'role_id'       => $roleId,
            'full_name'     => $validated['name'],
            'email'         => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'status'        => strtolower($validated['status'] ?? 'Active'),
        ];

        if (isset($validated['address']))        $userData['address']        = $validated['address'];
        if (isset($validated['contact_number'])) $userData['contact_number'] = $validated['contact_number'];

        $user = User::create($userData);

        $details  = "Created new user:\n";
        $details .= "Name: "  . $validated['name']  . "\n";
        $details .= "Email: " . $validated['email'] . "\n";
        $details .= "Role: "  . ucfirst($roleName)  . "\n";
        if (isset($validated['address']))        $details .= "\nAddress: " . $validated['address'];
        if (isset($validated['contact_number'])) $details .= "\nContact: " . $validated['contact_number'];

        LoginLog::create([
            'user_id'         => $request->user()?->id,
            'action'          => 'user_create',
            'email_attempted' => $validated['email'],
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'status'          => 'success',
            'details'         => $details,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data'    => [
                'id'             => $user->id,
                'name'           => $user->full_name,
                'email'          => $user->email,
                'role'           => ucfirst($roleName),
                'status'         => ucfirst($user->status),
                'created_at'     => $user->created_at,
                'last_login'     => $user->last_login,
                'address'        => $user->address ?? '',
                'contact_number' => $user->contact_number ?? '',
            ],
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $user->load('role');

        return response()->json([
            'data' => [
                'id'             => $user->id,
                'name'           => $user->full_name,
                'email'          => $user->email,
                'role'           => ucfirst($user->role->name),
                'status'         => ucfirst($user->status),
                'created_at'     => $user->created_at,
                'last_login'     => $user->last_login,
                'address'        => $user->address ?? '',
                'contact_number' => $user->contact_number ?? '',
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'email'          => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password'       => ['sometimes', 'required', 'string', Password::min(6)],
            'role'           => 'sometimes|required|in:Lawyer,Clerk',
            'address'        => 'nullable|string',
            'contact_number' => 'nullable|string|max:20',
            'status'         => 'sometimes|required|in:Active,Inactive',
        ]);

        $updateData = [];
        $changes    = [];

        if (isset($validated['name']) && $validated['name'] !== $user->full_name) {
            $updateData['full_name'] = $validated['name'];
            $changes[] = "name: '{$user->full_name}' → '{$validated['name']}'";
        }

        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $updateData['email'] = $validated['email'];
            $changes[] = "email: '{$user->email}' → '{$validated['email']}'";
        }

        if (isset($validated['password'])) {
            $updateData['password_hash']      = Hash::make($validated['password']);
            $updateData['must_change_password'] = true;
            $changes[] = "password: [reset by admin — user must change on next login]";

            // ── KEY FIX ─────────────────────────────────────────────────────
            // Delete all Sanctum tokens for this user immediately.
            // Without this the old token stays valid forever, /check-status
            // keeps returning 200, and the "session expired" modal never fires.
            // With this, the next poll (≤30 s) returns 401 → modal shown.
            // ────────────────────────────────────────────────────────────────
            $user->tokens()->delete();
        }

        if (isset($validated['role'])) {
            $newRoleName = strtolower($validated['role']);
            $oldRoleName = $user->role->name;

            if ($newRoleName !== $oldRoleName) {
                $roleId = \DB::table('roles')->where('name', $newRoleName)->value('id');
                $updateData['role_id'] = $roleId;
                $changes[] = "role: '" . ucfirst($oldRoleName) . "' → '" . ucfirst($newRoleName) . "'";
            }
        }

        if (isset($validated['address']) && $validated['address'] !== $user->address) {
            $updateData['address'] = $validated['address'];
            $oldAddress = $user->address ?? 'empty';
            $changes[] = "address: '{$oldAddress}' → '{$validated['address']}'";
        }

        if (isset($validated['contact_number']) && $validated['contact_number'] !== $user->contact_number) {
            $updateData['contact_number'] = $validated['contact_number'];
            $oldContact = $user->contact_number ?? 'empty';
            $changes[] = "contact: '{$oldContact}' → '{$validated['contact_number']}'";
        }

        if (isset($validated['status'])) {
            $newStatus = strtolower($validated['status']);
            $oldStatus = $user->status;

            if ($newStatus !== $oldStatus) {
                $updateData['status'] = $newStatus;
                $changes[] = "status: '" . ucfirst($oldStatus) . "' → '" . ucfirst($newStatus) . "'";
            }
        }

        if (empty($updateData)) {
            return response()->json([
                'message' => 'No changes detected',
                'data'    => [
                    'id'             => $user->id,
                    'name'           => $user->full_name,
                    'email'          => $user->email,
                    'role'           => ucfirst($user->role->name),
                    'status'         => ucfirst($user->status),
                    'created_at'     => $user->created_at,
                    'last_login'     => $user->last_login,
                    'address'        => $user->address ?? '',
                    'contact_number' => $user->contact_number ?? '',
                ],
            ]);
        }

        $user->update($updateData);
        $user->load('role');

        $details  = "Updated user: {$user->full_name} ({$user->email})\n";
        $details .= "Changes made:\n";
        foreach ($changes as $change) {
            $details .= "- {$change}\n";
        }

        LoginLog::create([
            'user_id'         => $request->user()?->id,
            'action'          => 'user_update',
            'email_attempted' => $user->email,
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'status'          => 'success',
            'details'         => $details,
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'data'    => [
                'id'             => $user->id,
                'name'           => $user->full_name,
                'email'          => $user->email,
                'role'           => ucfirst($user->role->name),
                'status'         => ucfirst($user->status),
                'created_at'     => $user->created_at,
                'last_login'     => $user->last_login,
                'address'        => $user->address ?? '',
                'contact_number' => $user->contact_number ?? '',
            ],
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user)
    {
        $userEmail = $user->email;
        $userName  = $user->full_name;
        $userRole  = ucfirst($user->role->name);

        $details  = "Deleted user:\n";
        $details .= "Name: {$userName}\n";
        $details .= "Email: {$userEmail}\n";
        $details .= "Role: {$userRole}\n";
        $details .= "Status at deletion: " . ucfirst($user->status);

        // Revoke all tokens before deleting so any active sessions are
        // immediately invalidated (they'll get 401 on next request).
        $user->tokens()->delete();

        $user->delete();

        LoginLog::create([
            'user_id'         => $request->user()?->id,
            'action'          => 'user_delete',
            'email_attempted' => $userEmail,
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'status'          => 'success',
            'details'         => $details,
        ]);

        return response()->json(['message' => 'User deleted successfully']);
    }
}