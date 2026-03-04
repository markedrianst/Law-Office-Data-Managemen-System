<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseChecklistResource;
use App\Models\CaseChecklist;
use App\Models\Cases;
use App\Models\User;
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
            'task'              => ['required', 'string', 'max:500'],
            'status'            => ['required', 'in:todo,in-progress,done'],
            'due_date'          => ['nullable', 'date', 'date_format:Y-m-d'],
            'assigned_clerk_id' => ['nullable', 'integer'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $clerkId = $validated['assigned_clerk_id'] ?? null;
        $user    = $clerkId ? User::find($clerkId) : null;

        $item = CaseChecklist::create([
            'task'              => $validated['task'],
            'status'            => $validated['status'],
            'due_date'          => $validated['due_date'] ?? null,
            'notes'             => $validated['notes']    ?? null,
            'assigned_clerk_id' => $user?->id,                         // ✅ save the ID
            'assigned_to'       => $user?->full_name ?? $user?->name,  // ✅ save the name
            'case_id'           => $case->id,
            'created_by'        => $request->user()->id,
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
            'task'              => ['sometimes', 'required', 'string', 'max:500'],
            'status'            => ['sometimes', 'required', 'in:todo,in-progress,done'],
            'due_date'          => ['nullable', 'date', 'date_format:Y-m-d'],
            'assigned_clerk_id' => ['nullable', 'integer'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        // Only update clerk fields when a real integer ID was sent.
        // null means "not provided / status-only update" — keep existing clerk.
        // To intentionally clear a clerk, the frontend must send 0 or omit the field.
        $clerkWasSent = array_key_exists('assigned_clerk_id', $validated)
                        && is_numeric($validated['assigned_clerk_id']);
        $clerkId      = $clerkWasSent ? (int) $validated['assigned_clerk_id'] : null;
        $user         = $clerkId ? User::find($clerkId) : null;

        $checklist->update([
            'task'     => $validated['task']   ?? $checklist->task,
            'status'   => $validated['status'] ?? $checklist->status,

            'due_date' => array_key_exists('due_date', $validated)
                            ? ($validated['due_date'] ?: null)
                            : $checklist->due_date,

            'notes'    => array_key_exists('notes', $validated)
                            ? ($validated['notes'] ?: null)
                            : $checklist->notes,

            // Only update clerk when a real numeric ID was sent — never wipe on status-only saves
            'assigned_clerk_id' => $clerkWasSent ? $user?->id            : $checklist->assigned_clerk_id,
            'assigned_to'       => $clerkWasSent ? ($user?->full_name ?? $user?->name) : $checklist->assigned_to,
        ]);

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