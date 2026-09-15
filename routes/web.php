<?php

use App\Http\Controllers\Api\AssetApiController;
use App\Http\Controllers\AssetAnalysisController;
use App\Http\Controllers\AssetDirectoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationAnalysisController;
use App\Http\Controllers\LogHistoryController;
use App\Http\Controllers\TagGeneratorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Public / Authentication Routes ---
Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Staff dashboard + self-service damage reporting (both roles may report an issue)
    Route::get('/staff_dashboard', [DashboardController::class, 'staffDashboard'])
        ->middleware('role:staff')
        ->name('staff_dashboard');

    Route::post('/damage-queue/report', [DamageReportController::class, 'store'])
        ->name('report_damage.store');

    // Asset-tracking API shared by the QR scanner on both the staff dashboard
    // and the asset directory.
    Route::get('/api/assets/tracking/{trackingNumber}', [AssetApiController::class, 'byTracking'])
        ->name('assets.get_by_tracking');

    // --- Asset Team management area ---
    Route::middleware('role:asset_team')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'assetDashboard'])->name('asset_dashboard');

        Route::get('/assets', [AssetDirectoryController::class, 'index'])->name('asset_directory');
        Route::post('/assets/assign', [AssetDirectoryController::class, 'assign'])->name('assets.assign');

        Route::post('/categories/store', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/update', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/delete', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/analysis/asset', [AssetAnalysisController::class, 'index'])->name('asset_analysis');
        Route::get('/analysis/location', [LocationAnalysisController::class, 'index'])->name('location_analysis');

        Route::get('/logs', [LogHistoryController::class, 'index'])->name('log_history');

        Route::get('/damage-queue', [DamageReportController::class, 'index'])->name('damage_queue');
        Route::patch('/damage-queue/{report}/status', [DamageReportController::class, 'updateStatus'])
            ->name('damage_queue.update_status');

        Route::get('/tags/next-number', [TagGeneratorController::class, 'getNextNumber'])->name('tags.next_number');
        Route::post('/tags/bulk-generate', [TagGeneratorController::class, 'storeBulkTags'])->name('generate_bulk_tags');

        Route::prefix('api')->group(function () {
            Route::get('/assets/by-type', [AssetApiController::class, 'byType'])->name('assets.by_type');
            Route::put('/assets/{asset}', [AssetApiController::class, 'update'])->name('assets.update');
            Route::delete('/assets/{asset}', [AssetApiController::class, 'destroy'])->name('assets.destroy');
        });
    });
});
