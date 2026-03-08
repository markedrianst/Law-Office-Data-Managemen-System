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
    // =========================================================================
    // SHARED BASE QUERY — reused by index() and export() to avoid duplication
    // =========================================================================

    /**
     * Returns a pre-joined DB query builder for the cases list/export.
     * All filters are applied here so both index() and export() stay DRY.
     */
    private function buildCaseQuery(Request $request, array $selectColumns): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('cases')
            ->select($selectColumns)
            ->leftJoin('case_categories AS cc', 'cc.id', '=', 'cases.category_id')
            ->leftJoin('clients         AS cl', 'cl.id', '=', 'cases.client_id')
            ->leftJoin('users           AS lw', 'lw.id', '=', 'cases.assigned_lawyer_id')
            ->leftJoin('users           AS ck', 'ck.id', '=', 'cases.assigned_clerk_id')
            ->leftJoin('case_stages     AS cs', 'cs.id', '=', 'cases.current_stage_id');

        if ($request->filled('case_status')) {
            $query->where('cases.case_status', $request->case_status);
        }
        if ($request->filled('priority')) {
            $query->where('cases.priority', $request->priority);
        }
        if ($request->filled('stage_id')) {
            $query->where('cases.current_stage_id', (int) $request->stage_id);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cases.case_code', 'like', $term)
                  ->orWhere('cases.title',   'like', $term)
                  ->orWhere('cl.full_name',  'like', $term);
            });
        }

        return $query;
    }

    /**
     * Maps sort_by param to the actual qualified column name.
     */
    private function resolveSortColumn(string $sortBy): string
    {
        return match ($sortBy) {
            'case_no'   => 'cases.case_no',
            'case_code' => 'cases.case_code',
            'title'     => 'cases.title',
            'priority'  => 'cases.priority',
            default     => 'cases.created_at',
        };
    }

    // =========================================================================
    // GET /admin/cases
    // =========================================================================

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

        $sortColumn = $this->resolveSortColumn($request->sort_by ?? 'created_at');
        $sortDir    = $request->sort_direction ?? 'desc';
        $perPage    = (int) ($request->per_page ?? 10);
        $page       = (int) ($request->page    ?? 1);

        $selectColumns = [
            'cases.id',
            'cases.case_no',
            'cases.case_code',
            'cases.title',
            'cases.priority',
            'cases.case_status',
            'cases.current_stage_id',
            'cases.category_id',
            'cases.client_id',
            'cases.assigned_lawyer_id',
            'cases.assigned_clerk_id',
            'cases.court_or_office',
            'cases.docket_no',
            'cases.summary',
            'cases.created_at',
            'cases.updated_at',
            'cc.name      AS category_name',
            'cl.full_name AS client_name',
            'lw.full_name AS lawyer_name',
            'ck.full_name AS clerk_name',
            'cs.name      AS stage_name',
        ];

        // ── Cached total count (keyed by filter combo) ──────────────────────
        $filterKey = md5(serialize($request->only(['search', 'case_status', 'priority', 'stage_id'])));
        $countCacheKey = "cases_count_{$filterKey}";

        $total = Cache::remember($countCacheKey, 60, function () use ($request) {
            // Lightweight count query — only joins what the search filter needs
            $q = DB::table('cases')
                ->leftJoin('clients AS cl', 'cl.id', '=', 'cases.client_id');

            if ($request->filled('case_status')) $q->where('cases.case_status', $request->case_status);
            if ($request->filled('priority'))    $q->where('cases.priority',    $request->priority);
            if ($request->filled('stage_id'))    $q->where('cases.current_stage_id', (int) $request->stage_id);

            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $q->where(fn($x) => $x
                    ->where('cases.case_code', 'like', $term)
                    ->orWhere('cases.title',   'like', $term)
                    ->orWhere('cl.full_name',  'like', $term));
            }

            return $q->count();
        });

        // ── Paginated rows ───────────────────────────────────────────────────
        $rows = $this->buildCaseQuery($request, $selectColumns)
            ->orderBy($sortColumn, $sortDir)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data' => $this->formatRaw($rows),
            'meta' => [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    // =========================================================================
    // GET /admin/cases/{id}
    // =========================================================================

    public function show(int|string $id): JsonResponse
    {
        $id = (int) $id;

        // ── Single-row flat query — avoids N+1 from Eloquent eager loading ──
        $case = Cache::remember("case_{$id}", 60, function () use ($id) {
            return DB::table('cases')
                ->select([
                    'cases.*',
                    'cc.name      AS category_name',
                    'cl.full_name AS client_name',
                    'lw.full_name AS lawyer_name',
                    'ck.full_name AS clerk_name',
                    'cs.name      AS stage_name',
                ])
                ->leftJoin('case_categories AS cc', 'cc.id', '=', 'cases.category_id')
                ->leftJoin('clients         AS cl', 'cl.id', '=', 'cases.client_id')
                ->leftJoin('users           AS lw', 'lw.id', '=', 'cases.assigned_lawyer_id')
                ->leftJoin('users           AS ck', 'ck.id', '=', 'cases.assigned_clerk_id')
                ->leftJoin('case_stages     AS cs', 'cs.id', '=', 'cases.current_stage_id')
                ->where('cases.id', $id)
                ->first();
        });

        if (!$case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return response()->json(['data' => $this->formatRaw([$case])[0]]);
    }

    // =========================================================================
    // GET /admin/cases/export?format=xlsx|pdf
    // =========================================================================

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

        $selectColumns = [
            'cases.case_no',
            'cases.case_code',
            'cases.title',
            'cases.priority',
            'cases.case_status',
            'cases.court_or_office',
            'cases.docket_no',
            'cases.summary',
            'cases.created_at',
            'cc.name      AS category_name',
            'cl.full_name AS client_name',
            'lw.full_name AS lawyer_name',
            'ck.full_name AS clerk_name',
            'cs.name      AS stage_name',
        ];

        $rows = $this->buildCaseQuery($request, $selectColumns)
            ->orderBy($this->resolveSortColumn($request->sort_by ?? 'created_at'), $request->sort_direction ?? 'desc')
            ->get();

        $format = $request->format;
        $now    = now()->format('Y-m-d');

        // ── EXCEL ─────────────────────────────────────────────────────────────
        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Cases');

            $headers = [
                'A' => 'Case Code',   'B' => 'Case No.',     'C' => 'Title',
                'D' => 'Category',    'E' => 'Client',        'F' => 'Lawyer',
                'G' => 'Clerk',       'H' => 'Stage',         'I' => 'Priority',
                'J' => 'Status',      'K' => 'Court / Office','L' => 'Docket No.',
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

            // ── Batch-write rows (faster than per-cell applyFromArray) ───────
            $rowNum    = 2;
            $evenStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]];

            foreach ($rows as $c) {
                $sheet->fromArray([
                    $c->case_code,
                    $c->case_no,
                    $c->title,
                    $c->category_name    ?? '—',
                    $c->client_name      ?? '—',
                    $c->lawyer_name      ?? '—',
                    $c->clerk_name       ?? '—',
                    $c->stage_name       ?? '—',
                    ucfirst($c->priority),
                    ucfirst($c->case_status),
                    $c->court_or_office  ?? '—',
                    $c->docket_no        ?? '—',
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

        // ── PDF ───────────────────────────────────────────────────────────────
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

    // =========================================================================
    // LOOKUP ENDPOINTS — all cached
    // =========================================================================

    public function categories(): JsonResponse
    {
        $data = Cache::remember('case_categories', 300, fn() =>
            CaseCategory::orderBy('name')->get(['id', 'name'])
        );

        return response()->json(['data' => $data]);
    }

    public function assignableUsers(Request $request): JsonResponse
    {
        $role     = $request->input('role');
        $limit    = (int) $request->input('limit', 100);
        $cacheKey = 'assignable_users_' . ($role ?? 'all') . "_{$limit}";

        $users = Cache::remember($cacheKey, 300, function () use ($role, $limit) {
            return User::where('status', 'active')
                ->select('id', 'full_name', 'role_id')
                ->orderBy('full_name')
                ->when(
                    $role && in_array($role, ['lawyer', 'clerk']),
                    fn($q) => $q->whereHas('role', fn($r) => $r->where('name', $role)),
                    fn($q) => $q->whereHas('role', fn($r) => $r->whereIn('name', ['lawyer', 'clerk']))
                )
                ->limit($limit)
                ->get()
                ->map(fn($u) => [
                    'id'   => $u->id,
                    'name' => $u->full_name,
                    'role' => $u->role?->name,
                ]);
        });

        return response()->json(['data' => $users]);
    }

    public function stages(Request $request): JsonResponse
    {
        $fields     = $request->input('fields', 'id,name,is_active');
        $fieldArray = explode(',', $fields);

        $data = Cache::remember('case_stages', 300, fn() =>
            CaseStage::orderBy('name')->get($fieldArray)
        );

        return response()->json(['data' => $data]);
    }

    public function listClients(Request $request): JsonResponse
    {
        $search     = $request->input('search', '');
        $limit      = (int) $request->input('limit', 50);
        $fields     = $request->input('fields', 'id,full_name,contact_no,email');
        $fieldArray = explode(',', $fields);
        $cacheKey   = 'clients_' . md5($search . $limit . implode(',', $fieldArray));

        $data = Cache::remember($cacheKey, 60, function () use ($search, $limit, $fieldArray) {
            return Client::orderBy('full_name')
                ->when($search, fn($q) => $q->where('full_name', 'like', "%{$search}%"))
                ->select($fieldArray)
                ->limit($limit)
                ->get();
        });

        return response()->json(['data' => $data]);
    }

    // =========================================================================
    // STORE  POST /admin/cases
    // =========================================================================

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
            $year    = date('Y');
            $now     = now();

            // ── Atomic sequence counter (no gap, no race condition) ──────────
            $seq = str_pad(
                Cases::whereYear('created_at', $year)->lockForUpdate()->count() + 1,
                4, '0', STR_PAD_LEFT
            );

            // ── Resolve initial stage ────────────────────────────────────────
            $stageId = !empty($validated['current_stage_id'])
                ? (int) $validated['current_stage_id']
                : CaseStage::where('is_active', true)->orderBy('id')->value('id');

            // ── Create case ──────────────────────────────────────────────────
            $case = Cases::create([
                'case_no'            => $validated['case_no'],
                'case_code'          => "{$year}-{$seq}",
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

            // ── Stage history ────────────────────────────────────────────────
            if ($stageId) {
                CaseStageHistory::create([
                    'case_id'       => $case->id,
                    'from_stage_id' => null,
                    'to_stage_id'   => $stageId,
                    'changed_by'    => $actorId,
                    'remarks'       => 'Initial stage set on case creation',
                ]);
            }

            // ── Activity logs — single bulk insert for performance ───────────
            $lawyerName = User::where('id', $validated['assigned_lawyer_id'])
                ->value('full_name') ?? "User ID #{$validated['assigned_lawyer_id']}";

            CaseActivityLog::insert([
                [
                    'case_id'    => $case->id,
                    'user_id'    => $actorId,
                    'action'     => 'created the case',
                    'details'    => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'case_id'    => $case->id,
                    'user_id'    => $actorId,
                    'action'     => 'assigned lawyer',
                    'details'    => $lawyerName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $this->clearCaseCache($case->id);

            return $case;
        });

        // ── Single-query flat fetch for the response ─────────────────────────
        return response()->json([
            'message' => 'Case created successfully.',
            'data'    => $this->fetchFormattedCase($case->id),
        ], 201);
    }

    // =========================================================================
    // UPDATE  PUT /admin/cases/{id}
    // =========================================================================

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

                // ── Update case ──────────────────────────────────────────────
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

                // ── Stage change history ─────────────────────────────────────
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

                // ── Activity logs — bulk insert ──────────────────────────────
                $logs = [];

                if (!empty($changes)) {
                    foreach ($changes as $field => $change) {
                        $logs[] = [
                            'case_id'    => $case->id,
                            'user_id'    => $actorId,
                            'action'     => "updated {$field}",
                            'details'    => json_encode($change),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    $logs[] = [
                        'case_id'    => $case->id,
                        'user_id'    => $actorId,
                        'action'     => 'updated case details',
                        'details'    => json_encode(['note' => 'No significant changes detected']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

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

    // =========================================================================
    // ARCHIVE  PATCH /admin/cases/{id}/archive
    // =========================================================================

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

    // =========================================================================
    // POST /admin/cases/clients/quick-create
    // =========================================================================

    public function quickCreateClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|max:150',
            'contact_no' => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:100|unique:clients,email',
            'address'    => 'nullable|string|max:255',
        ]);

        $client = Client::create($validated);

        return response()->json([
            'message' => 'Client created successfully.',
            'data'    => $client,
        ], 201);
    }

    // =========================================================================
    // GET /admin/cases/courts-offices
    // =========================================================================

    public function courtsOffices(Request $request): JsonResponse
    {
        $query = CourtOffice::active();

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        return response()->json([
            'data' => $query
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'is_active', 'sort_order']),
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Fetches a single case as a flat object via one SQL query
     * and formats it — used after store() and update() responses.
     */
    private function fetchFormattedCase(int $id): array
    {
        $row = DB::table('cases')
            ->select([
                'cases.*',
                'cc.name      AS category_name',
                'cl.full_name AS client_name',
                'lw.full_name AS lawyer_name',
                'ck.full_name AS clerk_name',
                'cs.name      AS stage_name',
            ])
            ->leftJoin('case_categories AS cc', 'cc.id', '=', 'cases.category_id')
            ->leftJoin('clients         AS cl', 'cl.id', '=', 'cases.client_id')
            ->leftJoin('users           AS lw', 'lw.id', '=', 'cases.assigned_lawyer_id')
            ->leftJoin('users           AS ck', 'ck.id', '=', 'cases.assigned_clerk_id')
            ->leftJoin('case_stages     AS cs', 'cs.id', '=', 'cases.current_stage_id')
            ->where('cases.id', $id)
            ->first();

        return $this->formatRaw([$row])[0];
    }

    /**
     * Formats a collection of raw DB rows (stdClass) or Eloquent models
     * into a consistent response array.
     */
    private function formatRaw($cases): array
    {
        $result = [];

        foreach ($cases as $c) {
            $isEloquent = $c instanceof \Illuminate\Database\Eloquent\Model;

            $result[] = [
                'id'                 => $c->id,
                'case_no'            => $c->case_no,
                'case_code'          => $c->case_code,
                'title'              => $c->title,
                'court_or_office'    => $c->court_or_office,
                'docket_no'          => $c->docket_no,
                'priority'           => $c->priority,
                'case_status'        => $c->case_status,
                'current_stage_id'   => $c->current_stage_id,
                'summary'            => $c->summary,
                'category_id'        => $c->category_id,
                'client_id'          => $c->client_id,
                'assigned_lawyer_id' => $c->assigned_lawyer_id,
                'assigned_clerk_id'  => $c->assigned_clerk_id,
                'created_at'         => $c->created_at,
                'updated_at'         => $c->updated_at,
                // Resolve from relation or flat join alias
                'category_name' => $isEloquent ? $c->category?->name    : $c->category_name,
                'client_name'   => $isEloquent ? $c->client?->full_name : $c->client_name,
                'lawyer_name'   => $isEloquent ? $c->lawyer?->full_name : $c->lawyer_name,
                'clerk_name'    => $isEloquent ? $c->clerk?->full_name  : $c->clerk_name,
                'stage_name'    => $isEloquent ? $c->currentStage?->name: $c->stage_name,
            ];
        }

        return $result;
    }

    /** Bust per-case and aggregate caches after any write. */
    private function clearCaseCache(int $caseId): void
    {
        Cache::forget("case_{$caseId}");
        Cache::forget('cases_total_count');
    }
}