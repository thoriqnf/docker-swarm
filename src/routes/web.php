<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// ============================================================
// Health Check — used by Docker HEALTHCHECK & Swarm
// ============================================================
Route::get('/health', function () {
    $dbStatus = 'fail';
    $redisStatus = 'fail';

    try {
        DB::connection()->getPdo();
        $dbStatus = 'ok';
    } catch (\Exception $e) {
        $dbStatus = 'fail: ' . $e->getMessage();
    }

    try {
        Redis::ping();
        $redisStatus = 'ok';
    } catch (\Exception $e) {
        $redisStatus = 'fail: ' . $e->getMessage();
    }

    $status = ($dbStatus === 'ok' && $redisStatus === 'ok') ? 'ok' : 'degraded';

    return response()->json([
        'status'   => $status,
        'db'       => $dbStatus,
        'redis'    => $redisStatus,
        'hostname' => gethostname(),       // Swarm demo: shows which node is serving
        'version'  => config('app.version', '1.0.0'),
        'ts'       => now()->toIso8601String(),
    ], $status === 'ok' ? 200 : 503);
})->name('health');

// ============================================================
// Public landing
// ============================================================
Route::get('/', function () {
    return redirect()->route('todos.index');
});

// ============================================================
// Authenticated routes
// ============================================================
Route::middleware(['auth'])->group(function () {

    // Todos
    Route::resource('todos', TodoController::class);
    Route::patch('todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
});

// ============================================================
// Breeze auth routes (login, register, password reset, etc.)
// ============================================================
require __DIR__ . '/auth.php';
