<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourtOffice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourtOfficeController extends Controller
{
    private const ALLOWED_TYPES = ['Court', 'Prosecutor', 'Agency', 'Other'];

    /**
     * GET /api/admin/courts
     * Paginated list with search, type, status filters and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CourtOffice::query();

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Sorting (whitelist)
        $allowedSorts = ['name', 'type', 'created_at'];

        $sortBy = in_array($request->input('sort_by'), $allowedSorts)
            ? $request->input('sort_by')
            : 'name';

        $sortDirection = $request->input('sort_direction') === 'desc'
            ? 'desc'
            : 'asc';

        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $courts  = $query->paginate($perPage);

        return response()->json([
            'data' => $courts->items(),
            'meta' => [
                'current_page' => $courts->currentPage(),
                'last_page'    => $courts->lastPage(),
                'per_page'     => $courts->perPage(),
                'total'        => $courts->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/courts/active
     * Lightweight dropdown list — active only.
     */
    public function active(): JsonResponse
    {
        $courts = CourtOffice::active()
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $courts]);
    }

    /**
     * GET /api/admin/courts/types
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'data' => self::ALLOWED_TYPES
        ]);
    }

    /**
     * GET /api/admin/courts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $court = CourtOffice::findOrFail($id);

        return response()->json(['data' => $court]);
    }

    /**
     * POST /api/admin/courts
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'type'         => 'nullable|in:' . implode(',', self::ALLOWED_TYPES),
            'address'      => 'nullable|string|max:500',
            'is_active'    => 'nullable|boolean',
            'contact_info' => 'nullable|string|max:500',
        ]);

        // Unique name check
        if (CourtOffice::where('name', trim($data['name']))->exists()) {
            throw ValidationException::withMessages([
                'name' => ['A court or office with this name already exists.'],
            ]);
        }

        $court = CourtOffice::create([
            'name'         => trim($data['name']),
            'type'         => $data['type'] ?? 'Court',
            'address'      => $data['address'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
            'contact_info' => $data['contact_info'] ?? null,
        ]);

        return response()->json([
            'message' => 'Court / office created successfully.',
            'data'    => $court,
        ], 201);
    }

    /**
     * PUT /api/admin/courts/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $court = CourtOffice::findOrFail($id);

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'type'         => 'nullable|in:' . implode(',', self::ALLOWED_TYPES),
            'address'      => 'nullable|string|max:500',
            'is_active'    => 'nullable|boolean',
            'contact_info' => 'nullable|string|max:500',
        ]);

        // Unique name check (exclude current record)
        if (
            CourtOffice::where('name', trim($data['name']))
                ->where('id', '!=', $id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => ['A court or office with this name already exists.'],
            ]);
        }

        $court->update([
            'name'         => trim($data['name']),
            'type'         => $data['type'] ?? $court->type,
            'address'      => $data['address'] ?? $court->address,
            'is_active'    => $data['is_active'] ?? $court->is_active,
            'contact_info' => $data['contact_info'] ?? $court->contact_info,
        ]);

        return response()->json([
            'message' => 'Court / office updated successfully.',
            'data'    => $court->fresh(),
        ]);
    }

    /**
     * PATCH /api/admin/courts/{id}/toggle-active
     */
    public function toggleActive(int $id): JsonResponse
    {
        $court = CourtOffice::findOrFail($id);

        $court->update([
            'is_active' => ! $court->is_active
        ]);

        return response()->json([
            'message' => $court->is_active
                ? 'Court activated.'
                : 'Court deactivated.',
            'data' => $court->fresh(),
        ]);
    }
}