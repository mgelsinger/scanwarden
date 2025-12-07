<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    UnitController,
    TeamController,
    ScanController,
    AuthController
};

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Units
    Route::get('/units', [UnitController::class, 'index']);
    Route::get('/units/{unit}', [UnitController::class, 'show']);

    // Teams
    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::put('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);
    Route::post('/teams/{team}/units', [TeamController::class, 'addUnit']);
    Route::delete('/teams/{team}/units/{unit}', [TeamController::class, 'removeUnit']);

    // Scans (with stricter rate limit)
    Route::post('/scan', [ScanController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/scans', [ScanController::class, 'index']);
});
