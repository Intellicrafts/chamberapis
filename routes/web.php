<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// ─── Dashboard (index page) ───────────────────────────────────────────────────
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// ─── Dashboard API endpoints (no auth required for internal use) ──────────────
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/health',       [DashboardController::class, 'health'])->name('health');
    Route::get('/metrics',      [DashboardController::class, 'metrics'])->name('metrics');
    Route::get('/logs',         [DashboardController::class, 'logs'])->name('logs');
    Route::get('/routes',       [DashboardController::class, 'routes'])->name('routes');
    Route::get('/db-stats',     [DashboardController::class, 'dbStats'])->name('db-stats');
    Route::post('/run',         [DashboardController::class, 'runCommand'])->name('run');
    Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('cache.clear');
    Route::post('/optimize',    [DashboardController::class, 'optimize'])->name('optimize');
});
