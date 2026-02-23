<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CaseController;

Route::post('/login',  [AuthenticatedSessionController::class, 'login']);
Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);
Route::put('/changepassword', [AuthenticatedSessionController::class, 'change']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);

    Route::get('/users',        [UserManagementController::class, 'index']);
    Route::post('/users',       [UserManagementController::class, 'store']);
    Route::get('/users/{user}', [UserManagementController::class, 'show']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);

    Route::get('/audit-logs',                 [AuditLogController::class, 'index']);
    Route::get('/audit-logs/export/csv',      [AuditLogController::class, 'export']);
    Route::get('/audit-logs/filters/actions', [AuditLogController::class, 'getActions']);
    Route::get('/audit-logs/{id}',            [AuditLogController::class, 'show']);
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

Route::get('audit-logs/case-activity', [AuditLogController::class, 'caseActivityLogs']);
Route::get('audit-logs/case-actions',  [AuditLogController::class, 'getCaseActions']);

    // Cases
    Route::get('cases',                    [CaseController::class, 'index']);
    Route::post('cases',                   [CaseController::class, 'store']);
    Route::get('cases/{id}',               [CaseController::class, 'show']);
    Route::put('cases/{id}',               [CaseController::class, 'update']);
    Route::patch('cases/{id}/archive',     [CaseController::class, 'archive']);
    Route::get('cases/{id}/activity-logs', [CaseController::class, 'activityLogs']);

    // Lookups — these must come BEFORE any wildcard routes
    Route::get('case-categories',   [CaseController::class, 'categories']);
    Route::get('users/assignable',  [CaseController::class, 'assignableUsers']);

    // Clients
    Route::get('clients',  [CaseController::class, 'listClients']);
    Route::post('clients', [CaseController::class, 'quickCreateClient']);
});