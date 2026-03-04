<?php

namespace App\Http\Controllers\Admin\CaseMaster;

use App\Http\Controllers\Controller;
use App\Models\Documents;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /**
     * GET /api/documents
     * Paginated list with optional search, category, is_active, and sorting.
     */
    public function index(Request $request)
    {
        $query = Documents::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where('type', 'like', "%{$search}%");
        }

        // Filter by category
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Filter by is_active
        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Sorting — only allow safe column names
        $allowedSorts = ['type', 'category', 'requires_approval', 'is_active', 'sort_order', 'created_at'];
        $sortBy        = in_array($request->input('sort_by'), $allowedSorts) ? $request->input('sort_by') : 'sort_order';
        $sortDirection = $request->input('sort_direction') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);

        $perPage  = (int) $request->input('per_page', 15);
        $perPage  = min(max($perPage, 5), 100); // clamp 5–100
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/documents/active
     * Flat list of active documents — for dropdowns.
     */
    public function active()
    {
        $documents = Documents::active()->ordered()->get();

        return response()->json(['data' => $documents]);
    }

    /**
     * GET /api/documents/{id}
     */
    public function show(Documents $document)
    {
        return response()->json(['data' => $document]);
    }

    /**
     * POST /api/documents
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'              => ['required', 'string', 'max:255', 'unique:documents,type'],
            'category'          => ['required', Rule::in(['Pleading', 'Letter', 'Evidence', 'Court Issuance', 'Other'])],
            'requires_approval' => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
            'sort_order'        => ['sometimes', 'integer', 'min:0'],
        ]);

        $document = Documents::create($validated);

        return response()->json(['data' => $document], 201);
    }

    /**
     * PUT /api/documents/{id}
     */
    public function update(Request $request, Documents $document)
    {
        $validated = $request->validate([
            'type'              => ['required', 'string', 'max:255', Rule::unique('documents', 'type')->ignore($document->id)],
            'category'          => ['required', Rule::in(['Pleading', 'Letter', 'Evidence', 'Court Issuance', 'Other'])],
            'requires_approval' => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
            'sort_order'        => ['sometimes', 'integer', 'min:0'],
        ]);

        $document->update($validated);

        return response()->json(['data' => $document]);
    }

    /**
     * PATCH /api/documents/{id}/toggle-active
     * Flip is_active boolean.
     */
    public function toggleActive(Documents $document)
    {
        $document->update(['is_active' => !$document->is_active]);

        return response()->json(['data' => $document]);
    }
}
