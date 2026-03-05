<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\FolderMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderTrackerController extends Controller
{
    // GET /admin/cases/{case}/folder-tracker
    public function index(Cases $case): JsonResponse
    {
        $records = FolderMovement::where('case_id', $case->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $records]);
    }

    // POST /admin/cases/{case}/folder-tracker
    public function store(Request $request, Cases $case): JsonResponse
    {
        $validated = $request->validate([
            'type'       => ['required', 'in:IN,OUT'],
            'from_to'    => ['nullable', 'string', 'max:255'],
            'date'       => ['required', 'date', 'date_format:Y-m-d'],
            'purpose'    => ['nullable', 'string', 'max:500'],
            'handled_by' => ['nullable', 'string', 'max:255'],
        ]);

        $record = FolderMovement::create([
            'case_id'     => $case->id,
            'recorded_by' => $request->user()->id,
            'type'        => $validated['type'],
            'from_to'     => $validated['from_to']    ?? null,
            'date'        => $validated['date'],
            'purpose'     => $validated['purpose']    ?? null,
            'handled_by'  => $validated['handled_by'] ?? null,
        ]);

        return response()->json([
            'message' => 'Folder movement recorded.',
            'data'    => $record,
        ], 201);
    }
}
