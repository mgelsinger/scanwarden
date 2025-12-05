<?php

use App\Http\Controllers\BattleMatchesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LoreController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\UnitsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Scan routes
    Route::get('/scan', [ScanController::class, 'create'])->name('scan.create');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
    Route::get('/scan/{scanRecord}', [ScanController::class, 'result'])->name('scan.result');

    // Units routes
    Route::get('/units', [UnitsController::class, 'index'])->name('units.index');
    Route::get('/units/{unit}', [UnitsController::class, 'show'])->name('units.show');
    Route::post('/units/{unit}/evolve', [UnitsController::class, 'evolve'])->name('units.evolve');

    // Teams routes
    Route::get('/teams', [TeamsController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamsController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamsController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}', [TeamsController::class, 'show'])->name('teams.show');
    Route::get('/teams/{team}/edit', [TeamsController::class, 'edit'])->name('teams.edit');
    Route::patch('/teams/{team}', [TeamsController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamsController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/units', [TeamsController::class, 'addUnit'])->name('teams.addUnit');
    Route::delete('/teams/{team}/units/{unit}', [TeamsController::class, 'removeUnit'])->name('teams.removeUnit');

    // Battle routes
    Route::get('/battles', [BattleMatchesController::class, 'index'])->name('battles.index');
    Route::get('/battles/create', [BattleMatchesController::class, 'create'])->name('battles.create');
    Route::post('/battles', [BattleMatchesController::class, 'store'])->name('battles.store');
    Route::get('/battles/{match}', [BattleMatchesController::class, 'show'])->name('battles.show');
    Route::delete('/battles/{match}', [BattleMatchesController::class, 'destroy'])->name('battles.destroy');

    // Leaderboard route
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Lore routes
    Route::get('/lore', [LoreController::class, 'index'])->name('lore.index');
    Route::get('/lore/{lore}', [LoreController::class, 'show'])->name('lore.show');
});

require __DIR__.'/auth.php';
