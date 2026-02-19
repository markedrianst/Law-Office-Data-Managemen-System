<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;



Route::post('/login', [AuthenticatedSessionController::class, 'login']);
Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);
    return $request->user();


});
