<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CaseStageController extends Controller
{
    // =========================================================================
    // MASTER DATA — manage the stage list itself
    // =========================================================================

    /**
     * GET /admin/master-data/case-stages
     * Returns all stages ordered by sort_order, then id.
     */
    public function index(): JsonResponse
    {
        $stages = DB::table('case_stages')
            ->orderBy('id')
            ->get(['id', 'name', 'is_active', 'created_at', 'updated_at']);

        return response()->json(['data' => $stages]);
    }

    /**
     * POST /admin/master-data/case-stages
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:case_stages,name',
        ]);

        $maxOrder = DB::table('case_stages') ?? 0;

        $id = DB::table('case_stages')->insertGetId([
            'name'        => $validated['name'],
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json([
            'message' => 'Stage created successfully.',
            'data'    => DB::table('case_stages')->where('id', $id)->first(),
        ], 201);
    }

    /**
     * PUT /admin/master-data/case-stages/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $stage = DB::table('case_stages')->where('id', $id)->first();
        if (! $stage) {
            return response()->json(['message' => 'Stage not found.'], 404);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:case_stages,name,' . $id,
        ]);

        DB::table('case_stages')->where('id', $id)->update([
            'name'        => $validated['name'],
            'updated_at'  => now(),
        ]);

        return response()->json([
            'message' => 'Stage updated successfully.',
            'data'    => DB::table('case_stages')->where('id', $id)->first(),
        ]);
    }

    /**
     * PATCH /admin/master-data/case-stages/{id}/toggle
     * Toggle is_active on/off.
     */
    public function toggle(int $id): JsonResponse
    {
        $stage = DB::table('case_stages')->where('id', $id)->first();
        if (! $stage) {
            return response()->json(['message' => 'Stage not found.'], 404);
        }

        DB::table('case_stages')->where('id', $id)->update([
            'is_active'  => ! $stage->is_active,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => $stage->is_active ? 'Stage deactivated.' : 'Stage activated.',
            'data'    => DB::table('case_stages')->where('id', $id)->first(),
        ]);
    }

    /**
     * PATCH /admin/master-data/case-stages/reorder
     * Expects: { stages: [{ id: 1, sort_order: 1 }, ...] }
     */

    // =========================================================================
    // PER-CASE STAGE ACTIONS
    // =========================================================================

    /**
     * GET /admin/cases/{caseId}/stages/history
     */
    public function history(int $caseId): JsonResponse
    {
        if (! DB::table('cases')->where('id', $caseId)->exists()) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        $history = DB::table('case_stage_histories')
            ->leftJoin('case_stages as from_stage', 'case_stage_histories.from_stage_id', '=', 'from_stage.id')
            ->leftJoin('case_stages as to_stage',   'case_stage_histories.to_stage_id',   '=', 'to_stage.id')
            ->leftJoin('users',                     'case_stage_histories.changed_by',     '=', 'users.id')
            ->select([
                'case_stage_histories.id',
                'from_stage.name as from_stage_name',
                'to_stage.name   as to_stage_name',
                'users.full_name as changed_by_name',
                'case_stage_histories.remarks',
                'case_stage_histories.created_at',
            ])
            ->where('case_stage_histories.case_id', $caseId)
            ->orderByDesc('case_stage_histories.created_at')
            ->get()
            ->map(fn ($h) => [
                'id'         => $h->id,
                'from'       => $h->from_stage_name ?? '— Start —',
                'to'         => $h->to_stage_name   ?? '—',
                'changed_by' => $h->changed_by_name ?? 'System',
                'remarks'    => $h->remarks,
                'time'       => \Carbon\Carbon::parse($h->created_at)->format('M d, Y g:i A'),
            ]);

        return response()->json(['data' => $history]);
    }

    /**
     * PUT /admin/cases/{caseId}/stage
     * Expects: { stage_id: int, remarks?: string }
     */
    public function updateCaseStage(Request $request, int $caseId): JsonResponse
    {
        $case = DB::table('cases')->where('id', $caseId)->first();
        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:case_stages,id',
            'remarks'  => 'nullable|string|max:500',
        ]);

        $newStageId = (int) $validated['stage_id'];

        if ($newStageId === (int) $case->current_stage_id) {
            return response()->json(['message' => 'Case is already at this stage.'], 422);
        }

        DB::transaction(function () use ($case, $newStageId, $validated) {
            DB::table('cases')->where('id', $case->id)->update([
                'current_stage_id' => $newStageId,
                'updated_at'       => now(),
            ]);

            DB::table('case_stage_histories')->insert([
                'case_id'       => $case->id,
                'from_stage_id' => $case->current_stage_id,
                'to_stage_id'   => $newStageId,
                'changed_by'    => auth()->id(),
                'remarks'       => $validated['remarks'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('case_activity_logs')->insert([
                'case_id'    => $case->id,
                'user_id'    => auth()->id(),
                'action'     => 'changed stage',
                'details'    => json_encode([
                    'from' => $case->current_stage_id,
                    'to'   => $newStageId,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $updated = DB::table('cases')
            ->leftJoin('case_stages', 'cases.current_stage_id', '=', 'case_stages.id')
            ->select(['cases.id', 'cases.current_stage_id', 'case_stages.name as stage_name'])
            ->where('cases.id', $case->id)
            ->first();

        return response()->json([
            'message' => 'Stage updated successfully.',
            'data'    => $updated,
        ]);
    }
}