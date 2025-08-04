<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommandController;

Route::get('/', function () {
    return view('welcome');
});

// Health check route for debugging
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'environment' => app()->environment(),
        'database' => try_connect_db(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    ]);
});

// Helper function to check database connection
// function try_connect_db() {
//     try {
//         \DB::connection()->getPdo();
//         return [
//             'connected' => true,
//             'name' => \DB::connection()->getDatabaseName(),
//             'driver' => \DB::connection()->getDriverName(),
//         ];
//     } catch (\Exception $e) {
//         return [
//             'connected' => false,
//             'error' => $e->getMessage(),
//         ];
//     }
// }

// Sanctum CSRF cookie route - this is crucial for your frontend
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->middleware('web');

// Command execution routes - no authentication required
// JSON API endpoint for command execution
Route::get('/api/run/{command?}', [CommandController::class, 'executeCommand'])->where('command', '.*');
Route::get('/api/run', [CommandController::class, 'executeCommand']);
Route::post('/api/run', [CommandController::class, 'executeCommand']);

// Web view for command execution
Route::get('/run/{command?}', [CommandController::class, 'executeCommandView'])->where('command', '.*');
Route::get('/run', [CommandController::class, 'executeCommandView']);

// Pull from main branch - JSON API endpoint
Route::get('/api/pull-main', [CommandController::class, 'pullFromMain']);

// Pull from main branch - Web view
Route::get('/pull-main', [CommandController::class, 'pullFromMainView']);

// Interactive terminal interface
Route::get('/terminal', [CommandController::class, 'terminal']);