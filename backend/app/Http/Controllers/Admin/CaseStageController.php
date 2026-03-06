<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\CaseActivityLog;
use App\Models\CaseStage;
use App\Models\CaseStageHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseStageController extends Controller
{
    // =========================================================================
    // MASTER DATA — manage the stage list
    // =========================================================================

    // GET /admin/master-data/case-stages
    public function index(): JsonResponse
    {
        $stages = CaseStage::orderBy('id')
            ->get(['id', 'name', 'is_active', 'created_at', 'updated_at']);

        return response()->json(['data' => $stages]);
    }

    // POST /admin/master-data/case-stages
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:case_stages,name',
        ]);

        $stage = CaseStage::create([
            'name'      => $validated['name'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Stage created successfully.',
            'data'    => $stage,
        ], 201);
    }

    // PUT /admin/master-data/case-stages/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $stage = CaseStage::find($id);

        if (! $stage) {
            return response()->json(['message' => 'Stage not found.'], 404);
        }

        $validated = $request->validate([
            'name' => "required|string|max:100|unique:case_stages,name,{$id}",
        ]);

        $stage->update(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Stage updated successfully.',
            'data'    => $stage->fresh(),
        ]);
    }

    // PATCH /admin/master-data/case-stages/{id}/toggle
    public function toggle(int $id): JsonResponse
    {
        $stage = CaseStage::find($id);

        if (! $stage) {
            return response()->json(['message' => 'Stage not found.'], 404);
        }

        $stage->update(['is_active' => ! $stage->is_active]);

        return response()->json([
            'message' => $stage->is_active ? 'Stage activated.' : 'Stage deactivated.',
            'data'    => $stage->fresh(),
        ]);
    }

    // =========================================================================
    // PER-CASE STAGE ACTIONS
    // =========================================================================

    // GET /admin/cases/{caseId}/stages/history
    public function history(int $caseId): JsonResponse
    {
        if (! Cases::where('id', $caseId)->exists()) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        $history = CaseStageHistory::with(['fromStage', 'toStage', 'changedBy'])
            ->where('case_id', $caseId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($h) => [
                'id'         => $h->id,
                'from'       => $h->fromStage?->name ?? '— Start —',
                'to'         => $h->toStage?->name   ?? '—',
                'changed_by' => $h->changedBy?->full_name ?? 'System',
                'remarks'    => $h->remarks,
                'time'       => Carbon::parse($h->created_at)->format('M d, Y g:i A'),
            ]);

        return response()->json(['data' => $history]);
    }

    // PUT /admin/cases/{caseId}/stage
    public function updateCaseStage(Request $request, int $caseId): JsonResponse
    {
        $case = Cases::find($caseId);

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:case_stages,id',
            'remarks'  => 'nullable|string|max:500',
        ]);

        $newStageId = (int) $validated['stage_id'];
        $oldStageId = (int) $case->current_stage_id;

        if ($newStageId === $oldStageId) {
            return response()->json(['message' => 'Case is already at this stage.'], 422);
        }

        DB::transaction(function () use ($case, $newStageId, $oldStageId, $validated) {
            $actorId = auth()->id();

            $case->update(['current_stage_id' => $newStageId]);

            CaseStageHistory::create([
                'case_id'       => $case->id,
                'from_stage_id' => $oldStageId ?: null,
                'to_stage_id'   => $newStageId,
                'changed_by'    => $actorId,
                'remarks'       => $validated['remarks'] ?? null,
            ]);

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => $actorId,
                'action'  => 'changed stage',
                'details' => json_encode(['from' => $oldStageId, 'to' => $newStageId]),
            ]);
        });

        // Return fresh case with new stage name
        $case->load('currentStage');

        return response()->json([
            'message' => 'Stage updated successfully.',
            'data'    => [
                'id'               => $case->id,
                'current_stage_id' => $case->current_stage_id,
                'stage_name'       => $case->currentStage?->name,
            ],
        ]);
    }
}