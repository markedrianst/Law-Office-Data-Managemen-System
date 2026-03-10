<?php

namespace App\Http\Controllers\Admin\CaseMaster;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\CaseActivityLog;
use App\Models\CaseCategory;
use App\Models\CaseStage;
use App\Models\CaseStageHistory;
use App\Models\Client;
use App\Models\CourtOffice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Barryvdh\DomPDF\Facade\Pdf;

class CaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'         => 'nullable|string|max:100',
            'case_status'    => 'nullable|in:active,closed,archived',
            'priority'       => 'nullable|in:low,normal,urgent',
            'stage_id'       => 'nullable|integer|exists:case_stages,id',
            'sort_by'        => 'nullable|in:case_no,case_code,title,priority,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:5|max:100',
        ]);

        $search   = $request->search       ?? null;
        $status   = $request->case_status  ?? null;
        $priority = $request->priority     ?? null;
        $stageId  = $request->filled('stage_id') ? (int) $request->stage_id : null;
        $sortCol  = Cases::resolveSortColumn($request->sort_by ?? 'created_at');
        $sortDir  = $request->sort_direction ?? 'desc';
        $perPage  = (int) ($request->per_page ?? 10);
        $page     = (int) ($request->page     ?? 1);
        $filterKey = md5(serialize(compact('search', 'status', 'priority', 'stageId')));
        $total = Cache::remember("cases_count_{$filterKey}", 60, fn() =>
            Cases::filteredCount($status, $priority, $stageId, $search)
        );
        $rows = Cases::withJoins(Cases::listColumns())
            ->ofStatus($status)
            ->ofPriority($priority)
            ->ofStage($stageId)
            ->search($search)
            ->orderBy($sortCol, $sortDir)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $lookups = null;
        if ($page === 1 && !$search && !$status && !$priority && !$stageId) {
            $lookups = [
                'categories' => CaseCategory::cachedAll(),
                'stages'     => CaseStage::cachedAll(),
                'courts'     => CourtOffice::cachedActive(),
                'users'      => User::cachedAssignable(),
                'clients'    => Client::cachedAll(),
            ];
        }

        return response()->json(array_filter([
            'data'    => $rows->map(fn($c) => Cases::formatRow($c))->values()->all(),
            'meta'    => [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
            'lookups' => $lookups,
        ], fn($v) => $v !== null));
    }

    public function show(int|string $id): JsonResponse
    {
        $id = (int) $id;

        $row = Cache::remember("case_{$id}", 60, fn() =>
            Cases::withJoins(Cases::listColumns())
                ->where('cases.id', $id)
                ->first()
        );

        if (!$row) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return response()->json(['data' => Cases::formatRow($row)]);
    }


    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'format'         => 'required|in:xlsx,pdf',
            'search'         => 'nullable|string|max:100',
            'case_status'    => 'nullable|in:active,closed,archived',
            'priority'       => 'nullable|in:low,normal,urgent',
            'stage_id'       => 'nullable|integer|exists:case_stages,id',
            'sort_by'        => 'nullable|in:case_no,case_code,title,priority,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);

        $rows = Cases::withJoins(Cases::exportColumns())
            ->ofStatus($request->case_status ?? null)
            ->ofPriority($request->priority ?? null)
            ->ofStage($request->filled('stage_id') ? (int) $request->stage_id : null)
            ->search($request->search ?? null)
            ->orderBy(
                Cases::resolveSortColumn($request->sort_by ?? 'created_at'),
                $request->sort_direction ?? 'desc'
            )
            ->get();

        $format = $request->format;
        $now    = now()->format('Y-m-d');

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Cases');

            $headers = [
                'A' => 'Case Code',    'B' => 'Case No.',      'C' => 'Title',
                'D' => 'Category',     'E' => 'Client',         'F' => 'Lawyer',
                'G' => 'Clerk',        'H' => 'Stage',          'I' => 'Priority',
                'J' => 'Status',       'K' => 'Court / Office', 'L' => 'Docket No.',
                'M' => 'Date Created',
            ];

            $headerStyle = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a4972']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            ];

            foreach ($headers as $col => $label) {
                $sheet->setCellValue("{$col}1", $label);
                $sheet->getStyle("{$col}1")->applyFromArray($headerStyle);
            }
            $sheet->getRowDimension(1)->setRowHeight(22);

            $rowNum    = 2;
            $evenStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]];

            foreach ($rows as $c) {
                $sheet->fromArray([
                    $c->case_code,
                    $c->case_no,
                    $c->title,
                    $c->category_name   ?? '—',
                    $c->client_name     ?? '—',
                    $c->lawyer_name     ?? '—',
                    $c->clerk_name      ?? '—',
                    $c->stage_name      ?? '—',
                    ucfirst($c->priority),
                    ucfirst($c->case_status),
                    $c->court_or_office ?? '—',
                    $c->docket_no       ?? '—',
                    $c->created_at ? Carbon::parse($c->created_at)->format('Y-m-d') : '—',
                ], null, "A{$rowNum}");

                if ($rowNum % 2 === 0) {
                    $sheet->getStyle("A{$rowNum}:M{$rowNum}")->applyFromArray($evenStyle);
                }
                $sheet->getStyle("A{$rowNum}:M{$rowNum}")->getFont()->setName('Arial')->setSize(9);
                $rowNum++;
            }

            foreach (array_keys($headers) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $total      = $rowNum - 2;
            $summaryRow = $rowNum + 1;
            $sheet->setCellValue("A{$summaryRow}", "Total cases exported: {$total}");
            $sheet->mergeCells("A{$summaryRow}:M{$summaryRow}");
            $sheet->getStyle("A{$summaryRow}")->applyFromArray([
                'font'      => ['italic' => true, 'color' => ['rgb' => '64748b'], 'size' => 9, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);

            ob_start();
            (new XlsxWriter($spreadsheet))->save('php://output');
            $content = ob_get_clean();

            return Response::make($content, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"cases_export_{$now}.xlsx\"",
                'Cache-Control'       => 'max-age=0',
            ]);
        }

        $pdf = Pdf::loadView('cases-pdf', [
            'rows'       => $rows,
            'exportedAt' => now()->format('F d, Y h:i A'),
            'filters'    => array_filter([
                'Status'   => $request->case_status,
                'Priority' => $request->priority,
                'Search'   => $request->search,
            ]),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("cases_export_{$now}.pdf");
    }

    public function activityLogs(int $id): JsonResponse
    {
        $logs = CaseActivityLog::where('case_id', $id)
            ->with('user:id,full_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $logs]);
    }
    public function categories(): JsonResponse
    {
        return response()->json(['data' => CaseCategory::cachedAll()]);
    }

    public function assignableUsers(Request $request): JsonResponse
    {
        $role  = $request->input('role');
        $limit = (int) $request->input('limit', 100);

        return response()->json(['data' => User::cachedAssignable($role, $limit)]);
    }

    public function stages(): JsonResponse
    {
        return response()->json(['data' => CaseStage::cachedAll()]);
    }

    public function listClients(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $limit  = (int) $request->input('limit', 50);

        if (!$search) {
            return response()->json(['data' => Client::cachedAll()]);
        }

        return response()->json([
            'data' => Client::search($search)
                ->orderBy('full_name')
                ->select(['id', 'full_name', 'contact_no', 'email'])
                ->limit($limit)
                ->get(),
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'case_no'            => 'required|string|max:180|unique:cases,case_no',
            'title'              => 'required|string|max:200',
            'category_id'        => 'nullable|exists:case_categories,id',
            'client_id'          => 'nullable|exists:clients,id',
            'court_or_office'    => 'nullable|string|max:180',
            'docket_no'          => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id'  => 'nullable|exists:users,id',
            'priority'           => 'required|in:low,normal,urgent',
            'case_status'        => 'required|in:active,closed,archived',
            'current_stage_id'   => 'nullable|integer|exists:case_stages,id',
            'summary'            => 'nullable|string|max:2000',
        ]);

        $case = DB::transaction(function () use ($validated) {
            $actorId = auth()->id();
            $year    = (int) date('Y');
            $now     = now();

            $stageId = !empty($validated['current_stage_id'])
                ? (int) $validated['current_stage_id']
                : CaseStage::firstActiveId();

            $case = Cases::create([
                'case_no'            => $validated['case_no'],
                'case_code'          => "{$year}-" . Cases::nextSequence($year),
                'title'              => $validated['title'],
                'category_id'        => $validated['category_id']        ?? null,
                'client_id'          => $validated['client_id']          ?? null,
                'court_or_office'    => $validated['court_or_office']    ?? null,
                'docket_no'          => $validated['docket_no']          ?? null,
                'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                'assigned_clerk_id'  => $validated['assigned_clerk_id']  ?? null,
                'priority'           => $validated['priority'],
                'case_status'        => $validated['case_status'],
                'current_stage_id'   => $stageId,
                'summary'            => $validated['summary']            ?? null,
                'created_by'         => $actorId,
            ]);

            if ($stageId) {
                CaseStageHistory::create([
                    'case_id'       => $case->id,
                    'from_stage_id' => null,
                    'to_stage_id'   => $stageId,
                    'changed_by'    => $actorId,
                    'remarks'       => 'Initial stage set on case creation',
                ]);
            }

            $lawyerName = User::where('id', $validated['assigned_lawyer_id'])->value('full_name')
                ?? "User ID #{$validated['assigned_lawyer_id']}";

            CaseActivityLog::insert([
                ['case_id' => $case->id, 'user_id' => $actorId, 'action' => 'created the case', 'details' => null,        'created_at' => $now, 'updated_at' => $now],
                ['case_id' => $case->id, 'user_id' => $actorId, 'action' => 'assigned lawyer',   'details' => $lawyerName, 'created_at' => $now, 'updated_at' => $now],
            ]);

            $this->clearCaseCache($case->id);

            return $case;
        });

        return response()->json([
            'message' => 'Case created successfully.',
            'data'    => $this->fetchFormattedCase($case->id),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $case = Cases::find($id);

        if (!$case) {
            return response()->json([
                'message' => 'Case not found.',
                'errors'  => ['id' => ["No case found with ID: {$id}"]],
            ], 404);
        }

        $validated = $request->validate([
            'case_no'            => ['required', 'string', 'max:180', "unique:cases,case_no,{$case->id},id"],
            'title'              => 'required|string|max:200',
            'category_id'        => 'nullable|exists:case_categories,id',
            'client_id'          => 'nullable|exists:clients,id',
            'court_or_office'    => 'nullable|string|max:180',
            'docket_no'          => 'nullable|string|max:80',
            'assigned_lawyer_id' => 'required|exists:users,id',
            'assigned_clerk_id'  => 'nullable|exists:users,id',
            'priority'           => 'required|in:low,normal,urgent',
            'case_status'        => 'required|in:active,closed,archived',
            'current_stage_id'   => 'nullable|integer|exists:case_stages,id',
            'summary'            => 'nullable|string|max:2000',
        ]);

        try {
            DB::transaction(function () use ($case, $validated) {
                $actorId     = auth()->id();
                $now         = now();
                $watchFields = ['case_no', 'title', 'priority', 'case_status', 'assigned_lawyer_id', 'assigned_clerk_id'];
                $changes     = [];

                foreach ($watchFields as $field) {
                    $old = $case->$field ?? null;
                    $new = $validated[$field] ?? null;
                    if ($old != $new) {
                        $changes[$field] = ['from' => $old, 'to' => $new];
                    }
                }

                $oldStageId = (int) $case->current_stage_id;
                $newStageId = !empty($validated['current_stage_id'])
                    ? (int) $validated['current_stage_id']
                    : $oldStageId;

                $case->update([
                    'case_no'            => $validated['case_no'],
                    'title'              => $validated['title'],
                    'category_id'        => $validated['category_id']        ?? null,
                    'client_id'          => $validated['client_id']          ?? null,
                    'court_or_office'    => $validated['court_or_office']    ?? null,
                    'docket_no'          => $validated['docket_no']          ?? null,
                    'assigned_lawyer_id' => $validated['assigned_lawyer_id'],
                    'assigned_clerk_id'  => $validated['assigned_clerk_id']  ?? null,
                    'priority'           => $validated['priority'],
                    'case_status'        => $validated['case_status'],
                    'current_stage_id'   => $newStageId,
                    'summary'            => $validated['summary']            ?? null,
                ]);

                if ($newStageId !== $oldStageId) {
                    CaseStageHistory::create([
                        'case_id'       => $case->id,
                        'from_stage_id' => $oldStageId ?: null,
                        'to_stage_id'   => $newStageId,
                        'changed_by'    => $actorId,
                        'remarks'       => 'Stage updated via case edit',
                    ]);
                    $changes['current_stage_id'] = ['from' => $oldStageId, 'to' => $newStageId];
                }

                $logs = !empty($changes)
                    ? array_map(fn($field, $change) => [
                        'case_id'    => $case->id,
                        'user_id'    => $actorId,
                        'action'     => "updated {$field}",
                        'details'    => json_encode($change),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], array_keys($changes), array_values($changes))
                    : [[
                        'case_id'    => $case->id,
                        'user_id'    => $actorId,
                        'action'     => 'updated case details',
                        'details'    => json_encode(['note' => 'No significant changes detected']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]];

                CaseActivityLog::insert($logs);

                $this->clearCaseCache($case->id);
            });

            return response()->json([
                'message' => 'Case updated successfully.',
                'data'    => $this->fetchFormattedCase($id),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating case.'], 500);
        }
    }

    public function archive(int $id): JsonResponse
    {
        $case = Cases::find($id);

        if (!$case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        DB::transaction(function () use ($case) {
            $case->update(['case_status' => 'archived']);

            CaseActivityLog::create([
                'case_id' => $case->id,
                'user_id' => auth()->id(),
                'action'  => 'archived the case',
                'details' => null,
            ]);

            $this->clearCaseCache($case->id);
        });

        return response()->json(['message' => 'Case archived successfully.']);
    }

    public function quickCreateClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|max:150',
            'contact_no' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100|unique:clients,email',
            'address'    => 'nullable|string|max:255',
        ]);

        $client = Client::create($validated);
        Client::bustCache();

        return response()->json([
            'message' => 'Client created successfully.',
            'data'    => $client,
        ], 201);
    }

    private function fetchFormattedCase(int $id): array
    {
        $row = Cases::withJoins(Cases::listColumns())
            ->where('cases.id', $id)
            ->first();

        return Cases::formatRow($row);
    }

    private function clearCaseCache(int $caseId): void
    {
        Cache::forget("case_{$caseId}");
        Cache::forget('cases_total_count');
    }
}