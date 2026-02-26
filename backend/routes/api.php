<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CaseController;
use App\Http\Controllers\Admin\CaseStageController;
use App\Http\Controllers\Admin\CaseCategoryController;
use App\Http\Controllers\Admin\CourtOfficeController;

Route::post('/login',         [AuthenticatedSessionController::class, 'login']);
Route::post('/logout',        [AuthenticatedSessionController::class, 'logout']);
Route::put('/changepassword', [AuthenticatedSessionController::class, 'change']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);

    Route::get('/check-status', function (Request $request) {
        $freshUser = \App\Models\User::select('id', 'status', 'role_id', 'password_hash')
            ->with('role:id,name')
            ->find($request->user()->id);

        if (!$freshUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($freshUser->status !== 'active') {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.'
            ], 403);
        }

        $tokenUser = $request->user();
        if ($tokenUser->getAuthPassword() !== $freshUser->password) {
            return response()->json([
                'message' => 'Your credentials have been changed. Please log in again.'
            ], 401);
        }

        return response()->json([
            'status' => 'active',
            'role'   => $freshUser->role->name ?? null,
        ]);
    });

    Route::get('/users',           [UserManagementController::class, 'index']);
    Route::post('/users',          [UserManagementController::class, 'store']);
    Route::get('/users/{user}',    [UserManagementController::class, 'show']);
    Route::put('/users/{user}',    [UserManagementController::class, 'update']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);

    Route::get('/audit-logs',                 [AuditLogController::class, 'index']);
    Route::get('/audit-logs/export/csv',      [AuditLogController::class, 'export']);
    Route::get('/audit-logs/filters/actions', [AuditLogController::class, 'getActions']);
    Route::get('/audit-logs/{id}',            [AuditLogController::class, 'show']);
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    // Audit Logs
    Route::get('audit-logs/case-activity', [AuditLogController::class, 'caseActivityLogs']);
    Route::get('audit-logs/case-actions',  [AuditLogController::class, 'getCaseActions']);

    // Cases
    Route::get('cases',                    [CaseController::class, 'index']);
    Route::post('cases',                   [CaseController::class, 'store']);
    Route::get('cases/{id}',               [CaseController::class, 'show']);
    Route::put('cases/{id}',               [CaseController::class, 'update']);
    Route::patch('cases/{id}/archive',     [CaseController::class, 'archive']);
    Route::get('cases/{id}/activity-logs', [CaseController::class, 'activityLogs']);

    // Case Stage - per-case actions
    Route::get('cases/{caseId}/stages/history', [CaseStageController::class, 'history']);
    Route::put('cases/{caseId}/stage',          [CaseStageController::class, 'updateCaseStage']);

    // Master Data - Case Stages
    // IMPORTANT: reorder must be before {id} or Laravel matches "reorder" as an ID
    Route::get('master-data/case-stages',               [CaseStageController::class, 'index']);
    Route::post('master-data/case-stages',              [CaseStageController::class, 'store']);
    Route::patch('master-data/case-stages/reorder',     [CaseStageController::class, 'reorder']);
    Route::put('master-data/case-stages/{id}',          [CaseStageController::class, 'update']);
    Route::patch('master-data/case-stages/{id}/toggle', [CaseStageController::class, 'toggle']);

    // Lookups
    Route::get('case-categories',  [CaseController::class, 'categories']);
    Route::get('users/assignable', [CaseController::class, 'assignableUsers']);

    // Clients
    Route::get('clients',  [CaseController::class, 'listClients']);
    Route::post('clients', [CaseController::class, 'quickCreateClient']);

// Case Categories
    Route::apiResource('case-categories', CaseCategoryController::class);
    Route::patch('case-categories/{id}/toggle',[CaseCategoryController::class, 'toggleStatus']);
    Route::get('courts-offices', [CaseController::class, 'courtsOffices']);

// ✅ CORRECT ORDER — static routes FIRST
Route::get   ('courts/active',             [CourtOfficeController::class, 'active']);
Route::get   ('courts/types',              [CourtOfficeController::class, 'types']);
Route::post  ('courts/reorder',            [CourtOfficeController::class, 'reorder']);
Route::patch ('courts/{id}/toggle-active', [CourtOfficeController::class, 'toggleActive']);
Route::apiResource('courts', CourtOfficeController::class);


});