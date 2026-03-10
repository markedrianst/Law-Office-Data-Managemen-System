<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistMovement;
use App\Models\FolderMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ApprovalsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status    = strtoupper($request->input('status',    'ALL'));
        $type      = strtolower($request->input('type',      'all'));
        $direction = strtoupper($request->input('direction', 'ALL'));
        $search    = $request->input('search', '');

        // ── Checklist movements ───────────────────────────────────────────────
        $checklistQ = ChecklistMovement::with([
            'checklist:id,task,assigned_to',
            'recorder:id,full_name',
            'approver:id,full_name',
            'case:id,case_code',           
        ]);

        if ($status !== 'ALL') {
            $checklistQ->where('approval_status', $status);
        }
        if ($direction !== 'ALL') {
            $checklistQ->where('type', $direction);
        }

        $checklistRows = $checklistQ->get()->map(fn($m) => array_merge(
            $m->toArray(),
            [
                '_source'   => 'checklist',
                'case_code' => $m->case?->case_code,
            ]
        ));

        $folderQ = FolderMovement::with([
            'recorder:id,full_name',
            'approver:id,full_name',
            'case:id,case_code',        
        ]);

        if ($status !== 'ALL') {
            $folderQ->where('approval_status', $status);
        }
        if ($direction !== 'ALL') {
            $folderQ->where('type', $direction);
        }

        $folderRows = $folderQ->get()->map(fn($m) => array_merge(
            $m->toArray(),
            [
                '_source'   => 'folder',
                'case_code' => $m->case?->case_code,
            ]
        ));

        // ── Merge ─────────────────────────────────────────────────────────────
        $all = collect([...$checklistRows, ...$folderRows]);

        // Type filter
        if ($type !== 'all') {
            $all = $all->filter(fn($m) => $m['_source'] === $type);
        }

        // Search filter
        if ($search) {
            $q = strtolower($search);
            $all = $all->filter(fn($m) =>
                str_contains(strtolower($m['case_code'] ?? ''), $q) ||
                str_contains(strtolower($m['checklist']['task'] ?? ''), $q) ||
                str_contains(strtolower($m['from_to'] ?? ''), $q) ||
                str_contains(strtolower($m['handled_by'] ?? ''), $q) ||
                str_contains(strtolower($m['recorder']['full_name'] ?? ''), $q)
            );
        }

        // Sort: PENDING first, then newest date
        $sorted = $all
            ->sortByDesc(fn($m) => $m['date'])
            ->sortBy(fn($m) => $m['approval_status'] === 'PENDING' ? 0 : 1)
            ->values();

        return response()->json([
            'data' => $sorted,
            'meta' => [
                'total'    => $sorted->count(),
                'pending'  => $sorted->where('approval_status', 'PENDING')->count(),
                'approved' => $sorted->where('approval_status', 'APPROVED')->count(),
                'rejected' => $sorted->where('approval_status', 'REJECTED')->count(),
            ],
        ]);
    }

    public function pendingCount(): JsonResponse
    {
        $count = ChecklistMovement::where('approval_status', 'PENDING')->count()
               + FolderMovement::where('approval_status', 'PENDING')->count();

        return response()->json(['count' => $count]);
    }

    public function approve(Request $request, string $source, int $movementId): JsonResponse
    {
        $validated = $request->validate([
            'approval_status' => ['required', 'in:APPROVED,REJECTED'],
        ]);

        $user = $request->user();

        if ($source === 'checklist') {
            $movement = ChecklistMovement::findOrFail($movementId);
            abort_if($movement->approval_status !== 'PENDING', 422, 'Already reviewed.');

            $movement->update([
                'approval_status' => $validated['approval_status'],
                'approved_by'     => $user->id,
                'approved_at'     => now(),
            ]);

            if ($validated['approval_status'] === 'APPROVED') {
                $isOut = $movement->type === 'OUT';
                if ($movement->checklist_id) {
                    \App\Models\CaseChecklist::where('id', $movement->checklist_id)
                        ->update(['is_out' => $isOut]);
                } else {
                    \App\Models\CaseChecklist::where('case_id', $movement->case_id)
                        ->update(['is_out' => $isOut]);
                }
            }

            return response()->json([
                'message' => 'Movement ' . strtolower($validated['approval_status']) . '.',
                'data'    => $movement->fresh()->load(['checklist:id,task,is_out', 'approver:id,full_name']),
            ]);
        }

        if ($source === 'folder') {
            $movement = FolderMovement::findOrFail($movementId);
            abort_if($movement->approval_status !== 'PENDING', 422, 'Already reviewed.');

            \Illuminate\Support\Facades\DB::transaction(function () use ($movement, $validated, $user) {
                $movement->update([
                    'approval_status' => $validated['approval_status'],
                    'approved_by'     => $user->id,
                    'approved_at'     => now(),
                ]);

                if ($validated['approval_status'] === 'APPROVED') {
                    \App\Models\Cases::where('id', $movement->case_id)
                        ->update(['is_out' => $movement->type === 'OUT']);
                }
            });

            return response()->json([
                'message' => 'Movement ' . strtolower($validated['approval_status']) . '.',
                'data'    => $movement->fresh()->load('approver:id,full_name'),
            ]);
        }

        return response()->json(['message' => 'Invalid source type.'], 422);
    }
}