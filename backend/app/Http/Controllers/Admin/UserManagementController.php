<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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
            // If no role filter, show only Lawyers and Clerks
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

        // Sorting
        $sortField = $request->get('sort_by', 'full_name');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        // Map frontend sort fields to database columns
        $fieldMap = [
            'name' => 'full_name',
            'email' => 'email',
            'role' => 'role_id',
            'status' => 'status',
            'last_login' => 'last_login'
        ];
        
        $dbField = $fieldMap[$sortField] ?? 'full_name';
        $query->orderBy($dbField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        // Transform users to match frontend format
        $transformedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => ucfirst($user->role->name), // Capitalize first letter
                'status' => ucfirst($user->status),
                'last_login' => $user->last_login,
                // Ready for address and contact fields when database is updated
                'address' => $user->address ?? '',
                'contact_number' => $user->contact_number ?? ''
            ];
        });

        return response()->json([
            'data' => $transformedUsers,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ]
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::min(6)],
            'role' => 'required|in:Lawyer,Clerk',
            'address' => 'nullable|string', // Ready for future use
            'contact_number' => 'nullable|string|max:20', // Ready for future use
            'status' => 'sometimes|in:Active,Inactive'
        ]);

        // Get role_id from role name
        $roleName = strtolower($validated['role']);
        $roleId = \DB::table('roles')->where('name', $roleName)->value('id');

        if (!$roleId) {
            return response()->json([
                'message' => 'Invalid role selected'
            ], 422);
        }

        $userData = [
            'role_id' => $roleId,
            'full_name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'status' => strtolower($validated['status'] ?? 'Active')
        ];

        // Add address and contact if provided (for future use)
        if (isset($validated['address'])) {
            $userData['address'] = $validated['address'];
        }
        
        if (isset($validated['contact_number'])) {
            $userData['contact_number'] = $validated['contact_number'];
        }

        $user = User::create($userData);

        return response()->json([
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => ucfirst($roleName),
                'status' => ucfirst($user->status),
                'last_login' => $user->last_login,
                'address' => $user->address ?? '',
                'contact_number' => $user->contact_number ?? ''
            ]
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Load role relationship
        $user->load('role');
        
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => ucfirst($user->role->name),
                'status' => ucfirst($user->status),
                'last_login' => $user->last_login,
                'address' => $user->address ?? '',
                'contact_number' => $user->contact_number ?? ''
            ]
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['sometimes', 'required', 'string', Password::min(6)],
            'role' => 'sometimes|required|in:Lawyer,Clerk',
            'address' => 'nullable|string', // Ready for future use
            'contact_number' => 'nullable|string|max:20', // Ready for future use
            'status' => 'sometimes|required|in:Active,Inactive'
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['full_name'] = $validated['name'];
        }

        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }

        if (isset($validated['password'])) {
            $updateData['password_hash'] = Hash::make($validated['password']);
        }

        if (isset($validated['role'])) {
            $roleName = strtolower($validated['role']);
            $roleId = \DB::table('roles')->where('name', $roleName)->value('id');
            $updateData['role_id'] = $roleId;
        }

        // Ready for address and contact fields
        if (isset($validated['address'])) {
            $updateData['address'] = $validated['address'];
        }

        if (isset($validated['contact_number'])) {
            $updateData['contact_number'] = $validated['contact_number'];
        }

        if (isset($validated['status'])) {
            $updateData['status'] = strtolower($validated['status']);
        }

        $user->update($updateData);
        $user->load('role');

        return response()->json([
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => ucfirst($user->role->name),
                'status' => ucfirst($user->status),
                'last_login' => $user->last_login,
                'address' => $user->address ?? '',
                'contact_number' => $user->contact_number ?? ''
            ]
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }


}