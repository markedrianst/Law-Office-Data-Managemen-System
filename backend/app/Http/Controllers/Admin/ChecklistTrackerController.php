<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\CaseChecklist;
use App\Models\ChecklistMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistTrackerController extends Controller
{
    // GET /admin/cases/{case}/checklist-tracker
    public function index(Cases $case): JsonResponse
    {
        $records = ChecklistMovement::where('case_id', $case->id)
            ->with([
                'checklist:id,task,assigned_to,is_out',
                'recorder:id,full_name',
                'approver:id,full_name',
            ])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Include available checklist items so the frontend can populate
        // the "Checklist Item" dropdown without a separate API call.
        $checklists = CaseChecklist::where('case_id', $case->id)
            ->select('id', 'task', 'is_out')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data'       => $records,
            'checklists' => $checklists,
        ]);
    }
    public function pending(Cases $case): JsonResponse
    {
        $records = ChecklistMovement::where('case_id', $case->id)
            ->where('approval_status', 'PENDING')
            ->with([
                'checklist:id,task,assigned_to,is_out',
                'recorder:id,full_name',
            ])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
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

        $user         = $request->user();
        $isPrivileged = $this->isPrivileged($user);

        // ── Create the movement record ────────────────────────────────────────
        $record = ChecklistMovement::create([
            'case_id'         => $case->id,
            'checklist_id'    => $validated['checklist_id'] ?? null,
            'recorded_by'     => $user->id,
            'type'            => $validated['type'],
            'from_to'         => $validated['from_to']    ?? null,
            'date'            => $validated['date'],
            'purpose'         => $validated['purpose']    ?? null,
            'handled_by'      => $validated['handled_by'] ?? null,
            // Admin / Lawyer → auto-approve; Clerk → stays PENDING
            'approval_status' => $isPrivileged ? 'APPROVED' : 'PENDING',
            'approved_by'     => $isPrivileged ? $user->id  : null,
            'approved_at'     => $isPrivileged ? now()      : null,
        ]);

        // ── Only flip is_out when movement is already APPROVED ────────────────
        if ($isPrivileged) {
            $this->flipIsOut($case->id, $validated['checklist_id'] ?? null, $validated['type'] === 'OUT');
        }

        return response()->json([
            'message' => $isPrivileged
                ? 'Checklist movement recorded and approved.'
                : 'Checklist movement recorded. Awaiting approval.',
            'data'    => $record->load('checklist:id,task,is_out'),
        ], 201);
    }

    // PATCH /admin/cases/{case}/checklist-tracker/{movement}/approve
    // Only admin and lawyer can hit this endpoint (enforce in routes via middleware)
    public function approve(Request $request, Cases $case, ChecklistMovement $movement): JsonResponse
    {
        abort_if($movement->case_id !== $case->id, 404, 'Movement not found for this case.');
        abort_if($movement->approval_status !== 'PENDING', 422, 'Movement has already been reviewed.');

        $user = $request->user();

        // Extra safety: ensure only admin / lawyer can approve
        abort_unless($this->isPrivileged($user), 403, 'Only admin or lawyer can approve movements.');

        $validated = $request->validate([
            'approval_status' => ['required', 'in:APPROVED,REJECTED'],
        ]);

        $movement->update([
            'approval_status' => $validated['approval_status'],
            'approved_by'     => $user->id,
            'approved_at'     => now(),
        ]);

        // ── Flip is_out ONLY when approved, never on rejection ────────────────
        if ($validated['approval_status'] === 'APPROVED') {
            $this->flipIsOut($case->id, $movement->checklist_id, $movement->type === 'OUT');
        }

        return response()->json([
            'message' => 'Movement ' . strtolower($validated['approval_status']) . '.',
            'data'    => $movement->fresh()->load('checklist:id,task,is_out'),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Flip is_out on the relevant checklist item(s).
     * Called only when a movement is definitively APPROVED.
     */
    private function flipIsOut(int $caseId, ?int $checklistId, bool $isOut): void
    {
        if ($checklistId) {
            CaseChecklist::where('id', $checklistId)
                ->update(['is_out' => $isOut]);
        } else {
            CaseChecklist::where('case_id', $caseId)
                ->update(['is_out' => $isOut]);
        }
    }


    private function isPrivileged(\App\Models\User $user): bool
    {
        // Works whether you store role as a string column or via a role relation
        $roleName = $user->role?->name ?? $user->role ?? '';
        return in_array(strtolower($roleName), ['admin', 'lawyer']);
    }
}