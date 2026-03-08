<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\FolderMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FolderTrackerController extends Controller
{
    // GET /admin/cases/{case}/folder-tracker
    public function index(Cases $case): JsonResponse
    {
        $records = FolderMovement::where('case_id', $case->id)
            ->with([
                'recorder:id,full_name',
                'approver:id,full_name',
            ])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $records,
            'case' => [
                'id'     => $case->id,
                'is_out' => $case->is_out,
            ],
        ]);
    }

    // GET /admin/cases/{case}/folder-tracker/pending
    // Dedicated endpoint for the approval panel — returns only PENDING movements
    public function pending(Cases $case): JsonResponse
    {
        $records = FolderMovement::where('case_id', $case->id)
            ->where('approval_status', 'PENDING')
            ->with('recorder:id,full_name')
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
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

        $user         = $request->user();
        $isPrivileged = $this->isPrivileged($user);

        DB::transaction(function () use ($validated, $user, $case, $isPrivileged, &$record) {

            $record = FolderMovement::create([
                'case_id'         => $case->id,
                'recorded_by'     => $user->id,
                'type'            => $validated['type'],
                'from_to'         => $validated['from_to'] ?? null,
                'date'            => $validated['date'],
                'purpose'         => $validated['purpose'] ?? null,
                'handled_by'      => $validated['handled_by'] ?? null,
                // Admin / Lawyer → auto-approve; Clerk → stays PENDING
                'approval_status' => $isPrivileged ? 'APPROVED' : 'PENDING',
                'approved_by'     => $isPrivileged ? $user->id  : null,
                'approved_at'     => $isPrivileged ? now()      : null,
            ]);

            // ── Only flip cases.is_out when already APPROVED ──────────────────
            if ($isPrivileged) {
                $case->update(['is_out' => $validated['type'] === 'OUT']);
            }
        });

        return response()->json([
            'message' => $isPrivileged
                ? 'Folder movement recorded and approved.'
                : 'Folder movement recorded. Awaiting approval.',
            'data'    => $record,
        ], 201);
    }

    // PATCH /admin/cases/{case}/folder-tracker/{movement}/approve
    // Only admin and lawyer can hit this endpoint (enforce in routes via middleware)
    public function approve(Request $request, Cases $case, FolderMovement $movement): JsonResponse
    {
        abort_if($movement->case_id !== $case->id, 404, 'Movement not found for this case.');
        abort_if($movement->approval_status !== 'PENDING', 422, 'Movement has already been reviewed.');

        $user = $request->user();

        abort_unless($this->isPrivileged($user), 403, 'Only admin or lawyer can approve movements.');

        $validated = $request->validate([
            'approval_status' => ['required', 'in:APPROVED,REJECTED'],
        ]);

        DB::transaction(function () use ($validated, $user, $case, $movement) {

            $movement->update([
                'approval_status' => $validated['approval_status'],
                'approved_by'     => $user->id,
                'approved_at'     => now(),
            ]);

            // ── Flip cases.is_out ONLY on APPROVED, never on REJECTED ─────────
            if ($validated['approval_status'] === 'APPROVED') {
                $case->update(['is_out' => $movement->type === 'OUT']);
            }
        });

        return response()->json([
            'message' => 'Movement ' . strtolower($validated['approval_status']) . '.',
            'data'    => $movement->fresh(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isPrivileged(\App\Models\User $user): bool
    {
        $roleName = $user->role?->name ?? $user->role ?? '';
        return in_array(strtolower($roleName), ['admin', 'lawyer']);
    }
}
