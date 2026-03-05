<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\ChecklistMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistTrackerController extends Controller
{
    // GET /admin/cases/{case}/checklist-tracker
    public function index(Cases $case): JsonResponse
    {
        $records = ChecklistMovement::where('case_id', $case->id)
            ->with('checklist:id,task,assigned_to')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        

        return response()->json(['data' => $records]);
    }

    // POST /admin/cases/{case}/checklist-tracker
    public function store(Request $request, Cases $case): JsonResponse
    {
        $validated = $request->validate([
            'type'         => ['required', 'in:IN,OUT'],
            'checklist_id' => ['nullable', 'integer', 'exists:case_checklists,id'],
            'from_to'      => ['nullable', 'string', 'max:255'],
            'date'         => ['required', 'date', 'date_format:Y-m-d'],
            'purpose'      => ['nullable', 'string', 'max:500'],
            'handled_by'   => ['nullable', 'string', 'max:255'],
        ]);

        $record = ChecklistMovement::create([
            'case_id'      => $case->id,
            'checklist_id' => $validated['checklist_id'] ?? null,
            'recorded_by'  => $request->user()->id,
            'type'         => $validated['type'],
            'from_to'      => $validated['from_to']    ?? null,
            'date'         => $validated['date'],
            'purpose'      => $validated['purpose']    ?? null,
            'handled_by'   => $validated['handled_by'] ?? null,
        ]);

        return response()->json([
            'message' => 'Checklist movement recorded.',
            'data'    => $record->load('checklist:id,task'),
        ], 201);
    }
}
