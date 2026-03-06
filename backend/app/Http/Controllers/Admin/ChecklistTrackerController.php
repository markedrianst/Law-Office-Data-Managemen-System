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
            ->with('checklist:id,task,assigned_to,is_out')
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


        // Flip is_out on the checklist item(s)
        // OUT → document left the office  → is_out = true
        // IN  → document returned         → is_out = false
        $isOut = $validated['type'] === 'OUT';
        if (!empty($validated['checklist_id'])) {
            \App\Models\CaseChecklist::where('id', $validated['checklist_id'])
                ->update(['is_out' => $isOut]);
        } else {
            \App\Models\CaseChecklist::where('case_id', $case->id)
                ->update(['is_out' => $isOut]);
        }

        return response()->json([
            'message' => 'Checklist movement recorded.',
            'data'    => $record->load('checklist:id,task,is_out'),
        ], 201);
    }

    // PATCH /admin/cases/{case}/checklist-tracker/{movement}/approve
    public function approve(Request $request, Cases $case, ChecklistMovement $movement): JsonResponse
    {
        abort_if($movement->case_id !== $case->id, 404, 'Movement not found for this case.');

        $validated = $request->validate([
            'approval_status' => ['required', 'in:APPROVED,REJECTED'],
        ]);

        $movement->update([
            'approval_status' => $validated['approval_status'],
            'approved_by'     => $request->user()->id,
            'approved_at'     => now(),
        ]);

        return response()->json([
            'message' => 'Movement ' . strtolower($validated['approval_status']) . '.',
            'data'    => $movement->fresh()->load('checklist:id,task,is_out'),
        ]);
    }
}