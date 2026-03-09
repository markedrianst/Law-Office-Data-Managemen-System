<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CaseMaster\CaseController;
use App\Http\Controllers\Admin\CaseStageController;
use App\Http\Controllers\Admin\CaseCategoryController;
use App\Http\Controllers\Admin\CourtOfficeController;
use App\Http\Controllers\Admin\CaseMaster\DocumentController;
use App\Http\Controllers\Admin\CaseChecklistController;
use App\Http\Controllers\Admin\ChecklistTrackerController;
use App\Http\Controllers\Admin\FolderTrackerController;
use App\Http\Controllers\Admin\ApprovalsController;
use App\Http\Controllers\Admin\NotificationController;


Route::post('/login',         [AuthenticatedSessionController::class, 'login']);
Route::post('/logout',        [AuthenticatedSessionController::class, 'logout']);
Route::put ('/changepassword', [AuthenticatedSessionController::class, 'change']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    // ── User management (admin only) ──────────────────────────────────────────
    Route::get   ('/users',           [UserManagementController::class, 'index']);
    Route::post  ('/users',           [UserManagementController::class, 'store']);
    Route::get   ('/users/{user}',    [UserManagementController::class, 'show']);
    Route::put   ('/users/{user}',    [UserManagementController::class, 'update']);
    Route::delete('/users/{user}',    [UserManagementController::class, 'destroy']);

    // ── Audit logs ────────────────────────────────────────────────────────────
    Route::get('/audit-logs',                 [AuditLogController::class, 'index']);
    Route::get('/audit-logs/export/csv',      [AuditLogController::class, 'export']);
    Route::get('/audit-logs/filters/actions', [AuditLogController::class, 'getActions']);
    Route::get('/audit-logs/{id}',            [AuditLogController::class, 'show']);

    // ── In-app notifications (all authenticated users) ────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get ('/',          [NotificationController::class, 'index']);      // paginated list
        Route::get ('/unread-count', [NotificationController::class, 'unreadCount']); // badge
        Route::patch('/{id}/read', [NotificationController::class, 'markRead']);  // mark one read
        Route::post ('/read-all', [NotificationController::class, 'readAll']);    // mark all read
    });
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    // ── Audit Logs ────────────────────────────────────────────────────────────
    Route::get('audit-logs/case-activity', [AuditLogController::class, 'caseActivityLogs']);
    Route::get('audit-logs/case-actions',  [AuditLogController::class, 'getCaseActions']);

    // ── Cases ─────────────────────────────────────────────────────────────────
    // READ  → admin, lawyer, clerk (clerk sees only their assigned cases — enforced in controller)
    // WRITE → admin, lawyer only  (enforced inside CaseController)
    Route::get   ('cases',                    [CaseController::class, 'index']);
    Route::post  ('cases',                    [CaseController::class, 'store']);          // admin/lawyer
    Route::get   ('cases/export',             [CaseController::class, 'export']);         // admin/lawyer
    Route::get   ('cases/{id}',               [CaseController::class, 'show']);
    Route::put   ('cases/{id}',               [CaseController::class, 'update']);         // admin/lawyer
    Route::patch ('cases/{id}/archive',       [CaseController::class, 'archive']);        // admin/lawyer
    Route::get   ('cases/{id}/activity-logs', [CaseController::class, 'activityLogs']);

    // ── Case Stage — per-case actions ─────────────────────────────────────────
    Route::get('cases/{caseId}/stages/history', [CaseStageController::class, 'history']);
    Route::put('cases/{caseId}/stage',          [CaseStageController::class, 'updateCaseStage']); // admin/lawyer

    // ── Master Data — Case Stages ─────────────────────────────────────────────
    Route::get   ('master-data/case-stages',             [CaseStageController::class, 'index']);
    Route::post  ('master-data/case-stages',             [CaseStageController::class, 'store']);
    Route::patch ('master-data/case-stages/reorder',     [CaseStageController::class, 'reorder']);
    Route::put   ('master-data/case-stages/{id}',        [CaseStageController::class, 'update']);
    Route::patch ('master-data/case-stages/{id}/toggle', [CaseStageController::class, 'toggle']);

    // ── Lookups ───────────────────────────────────────────────────────────────
    Route::get('case-categories',  [CaseController::class, 'categories']);
    Route::get('case-stages',      [CaseController::class, 'stages']);
    Route::get('users/assignable', [CaseController::class, 'assignableUsers']);

    // ── Clients ───────────────────────────────────────────────────────────────
    Route::get ('clients', [CaseController::class, 'listClients']);
    Route::post('clients', [CaseController::class, 'quickCreateClient']); // admin/lawyer only

    // ── Case Categories ───────────────────────────────────────────────────────
    Route::apiResource('case-categories', CaseCategoryController::class);
    Route::patch('case-categories/{id}/toggle', [CaseCategoryController::class, 'toggleStatus']);

    // ── Courts / Offices ──────────────────────────────────────────────────────
    Route::get   ('courts/active',             [CourtOfficeController::class, 'active']);
    Route::get   ('courts/types',              [CourtOfficeController::class, 'types']);
    Route::post  ('courts/reorder',            [CourtOfficeController::class, 'reorder']);
    Route::patch ('courts/{id}/toggle-active', [CourtOfficeController::class, 'toggleActive']);
    Route::apiResource('courts', CourtOfficeController::class);
    // Backward-compat alias — CaseFormModal.vue calls this URL directly
    Route::get   ('courts-offices',            [CourtOfficeController::class, 'active']);

    // ── Checklist ─────────────────────────────────────────────────────────────
    // READ  → all roles (clerk sees own cases only)
    // WRITE → admin/lawyer only (enforced in CaseChecklistController)
    Route::prefix('cases/{case}/checklist')->group(function () {
        Route::get   ('/',                   [CaseChecklistController::class, 'index']);
        Route::post  ('/',                   [CaseChecklistController::class, 'store']);          // admin/lawyer
        Route::get   ('/{checklist}',        [CaseChecklistController::class, 'show']);
        Route::put   ('/{checklist}',        [CaseChecklistController::class, 'update']);         // admin/lawyer
        Route::delete('/{checklist}',        [CaseChecklistController::class, 'destroy']);        // admin/lawyer
        Route::patch ('/{checklist}/status', [CaseChecklistController::class, 'updateStatus']);   // admin/lawyer
    });

    // ── Checklist Tracker & Folder Tracker ────────────────────────────────────
    // ALL ROLES may record movements.
    //   admin/lawyer → auto-APPROVED
    //   clerk        → PENDING until approved
    // APPROVE endpoint → admin/lawyer only (enforced in controller)
    Route::prefix('cases/{case}')->group(function () {

        // -- Checklist Tracker -------------------------------------------------
        Route::get  ('checklist-tracker',                    [ChecklistTrackerController::class, 'index']);
        Route::post ('checklist-tracker',                    [ChecklistTrackerController::class, 'store']);
        Route::get  ('checklist-tracker/pending',            [ChecklistTrackerController::class, 'pending']);
        Route::patch('checklist-tracker/{movement}/approve', [ChecklistTrackerController::class, 'approve']); // admin/lawyer

        // -- Folder Tracker ----------------------------------------------------
        Route::get  ('folder-tracker',                       [FolderTrackerController::class, 'index']);
        Route::post ('folder-tracker',                       [FolderTrackerController::class, 'store']);
        Route::get  ('folder-tracker/pending',               [FolderTrackerController::class, 'pending']);
        Route::patch('folder-tracker/{movement}/approve',    [FolderTrackerController::class, 'approve']); // admin/lawyer
    });

    // =========================================================================
    // GLOBAL APPROVALS MODULE
    // Admin & lawyer only — enforced inside ApprovalsController (role checked).
    // =========================================================================
    Route::prefix('approvals')->group(function () {
        Route::get  ('pending-count',                [ApprovalsController::class, 'pendingCount']);
        Route::get  ('/',                            [ApprovalsController::class, 'index']);
        Route::patch('/{source}/{movement}/approve', [ApprovalsController::class, 'approve']); // admin/lawyer
    });

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::prefix('documents')->controller(DocumentController::class)->group(function () {
        Route::get   ('/',                         'index');
        Route::get   ('/active',                   'active');
        Route::get   ('/{document}',               'show');
        Route::post  ('/',                         'store');
        Route::put   ('/{document}',               'update');
        Route::patch ('/{document}/toggle-active', 'toggleActive');
    });

});