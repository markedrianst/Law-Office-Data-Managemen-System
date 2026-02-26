<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseChecklistResource;
use App\Models\CaseChecklist;
use App\Models\Cases;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseChecklistController extends Controller
{
    // GET /admin/cases/{case}/checklist
    public function index(Cases $case)
    {
        $items = CaseChecklist::forCase($case->id)
            ->when(request('status'), fn($q) => $q->byStatus(request('status')))
            ->orderBy('created_at', 'asc')
            ->get();

        return CaseChecklistResource::collection($items);
    }

    // POST /admin/cases/{case}/checklist
    public function store(Request $request, Cases $case): JsonResponse
    {
        $validated = $request->validate([
            'task'        => ['required', 'string', 'max:500'],
            'status'      => ['required', 'in:todo,in-progress,done'],
            'due_date'    => ['nullable', 'date', 'date_format:Y-m-d'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $item = CaseChecklist::create([
            ...$validated,
            'case_id'    => $case->id,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'data'    => new CaseChecklistResource($item),
        ], 201);
    }

    // GET /admin/cases/{case}/checklist/{checklist}
    public function show(Cases $case, CaseChecklist $checklist): JsonResponse
    {
        $this->authorize($checklist, $case);

        return response()->json([
            'data' => new CaseChecklistResource($checklist),
        ]);
    }

    // PUT /admin/cases/{case}/checklist/{checklist}
    public function update(Request $request, Cases $case, CaseChecklist $checklist): JsonResponse
    {
        $this->authorize($checklist, $case);

        $validated = $request->validate([
            'task'        => ['sometimes', 'required', 'string', 'max:500'],
            'status'      => ['sometimes', 'required', 'in:todo,in-progress,done'],
            'due_date'    => ['nullable', 'date', 'date_format:Y-m-d'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $checklist->update($validated);

        return response()->json([
            'message' => 'Task updated successfully.',
            'data'    => new CaseChecklistResource($checklist->fresh()),
        ]);
    }

    // DELETE /admin/cases/{case}/checklist/{checklist}
    public function destroy(Cases $case, CaseChecklist $checklist): JsonResponse
    {
        $this->authorize($checklist, $case);

        $checklist->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }

    // PATCH /admin/cases/{case}/checklist/{checklist}/status
    public function updateStatus(Cases $case, CaseChecklist $checklist): JsonResponse
    {
        $this->authorize($checklist, $case);

        $validated = request()->validate([
            'status' => ['required', 'in:todo,in-progress,done'],
        ]);

        $checklist->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status updated.',
            'data'    => new CaseChecklistResource($checklist->fresh()),
        ]);
    }

    // ── Guard: ensure task belongs to the given case ───────────────────────
    private function authorize(CaseChecklist $checklist, Cases $case): void
    {
        abort_if($checklist->case_id !== $case->id, 404, 'Task not found for this case.');
    }
}