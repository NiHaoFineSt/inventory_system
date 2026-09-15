<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Asset;
use App\Http\Controllers\TagGeneratorController;
use App\Http\Controllers\DamageReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Public / Authentication Routes ---
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $user = Auth::user();

        if (in_array(strtolower($user->role ?? ''), ['asset team', 'admin', 'asset_team'])) {
            return redirect()->intended(route('asset_dashboard'));
        }

        return redirect()->intended(route('staff_dashboard'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.post');

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::post('/register', function () {
    return redirect()->route('login');
})->name('register.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// --- Main Application Navigation Routes ---
Route::middleware(['auth'])->group(function () {

    // Dashboards
    Route::get('/dashboard', function () {
        $assets = Asset::all();
        return view('asset_dashboard', compact('assets'));
    })->name('asset_dashboard');

    Route::get('/staff_dashboard', function () {
        $user = Auth::user();

        // Safely retrieve damage reports for this user depending on available table columns
        $reports = [];
        if (Schema::hasTable('damage_reports')) {
            $hasReportedBy = Schema::hasColumn('damage_reports', 'reported_by');
            $hasUserId     = Schema::hasColumn('damage_reports', 'user_id');

            $reports = DB::table('damage_reports')
                ->where(function ($query) use ($user, $hasReportedBy, $hasUserId) {
                    if ($hasReportedBy) {
                        $query->where('reported_by', $user->name ?? '')
                              ->orWhere('reported_by', $user->email ?? '');
                    }
                    
                    if ($hasUserId && isset($user->id)) {
                        if ($hasReportedBy) {
                            $query->orWhere('user_id', $user->id);
                        } else {
                            $query->where('user_id', $user->id);
                        }
                    }
                })
                ->latest()
                ->get();
        }

        // Retrieve assigned assets matching the user's name or email in location
        $assignedAssets = Asset::where(function ($query) use ($user) {
            $query->where('location', 'LIKE', '%' . $user->name . '%');
            if (!empty($user->email)) {
                $query->orWhere('location', 'LIKE', '%' . $user->email . '%');
            }
        })->get();

        return view('staff_dashboard', compact('reports', 'user', 'assignedAssets'));
    })->name('staff_dashboard');

    // Asset Directory
    Route::get('/assets', function () {
        $assets = Asset::all();

        $categories = Asset::whereNotNull('category')
                           ->pluck('category')
                           ->unique()
                           ->values();

        $types = Asset::select('asset_name', 'category')
                      ->whereNotNull('asset_name')
                      ->distinct()
                      ->get();

        $summaryData = Asset::select(
                'category',
                'asset_name',
                DB::raw('COUNT(*) as total_count'),
                DB::raw("SUM(CASE WHEN LOWER(status) IN ('active', 'operational', 'good') THEN 1 ELSE 0 END) as active_count"),
                DB::raw("SUM(CASE WHEN LOWER(status) IN ('pending repair', 'damaged', 'issue') THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN location IS NOT NULL AND TRIM(location) != '' AND LOWER(TRIM(location)) != 'unassigned' THEN 1 ELSE 0 END) as assigned_count"),
                DB::raw("SUM(CASE WHEN location IS NULL OR TRIM(location) = '' OR LOWER(TRIM(location)) = 'unassigned' THEN 1 ELSE 0 END) as unassigned_count")
            )
            ->groupBy('category', 'asset_name')
            ->get();

        return view('asset_directory', compact('assets', 'categories', 'types', 'summaryData'));
    })->name('asset_directory');

    // Asset Assignment Action Endpoint
    Route::post('/assets/assign', function (Request $request) {
        $validated = $request->validate([
            'asset_name' => 'required|string',
            'category'   => 'required|string',
            'department' => 'required|string',
            'employee'   => 'nullable|string',
            'quantity'   => 'required|integer|min:1',
        ]);

        $unassignedAssets = Asset::where('asset_name', $validated['asset_name'])
            ->where('category', $validated['category'])
            ->where(function ($query) {
                $query->whereNull('location')
                      ->orWhere('location', '')
                      ->orWhereRaw("LOWER(TRIM(location)) = 'unassigned'");
            })
            ->take($validated['quantity'])
            ->get();

        if ($unassignedAssets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No unassigned units available for this asset type.'
            ], 422);
        }

        $assignedLocation = $validated['department'];
        if (!empty($validated['employee'])) {
            $assignedLocation .= ' - ' . $validated['employee'];
        }

        foreach ($unassignedAssets as $asset) {
            $asset->location = $assignedLocation;
            $asset->status = 'Active';
            $asset->qr_code_payload = $asset->tracking_number . ' | ' . $assignedLocation;
            $asset->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned {$unassignedAssets->count()} unit(s) to {$assignedLocation}."
        ]);
    })->name('assets.assign');

    // Asset Analysis
    Route::get('/analysis/asset', function () {
        $assets = Asset::all();
        $totalCount = $assets->count();

        $categories = Asset::whereNotNull('category')->pluck('category')->unique()->values();
        $conditions = Asset::whereNotNull('condition')->pluck('condition')->unique()->values();
        $departments = collect([]);
        $statuses = Asset::whereNotNull('status')->pluck('status')->unique()->values();
        $types = Asset::select('asset_name', 'category')->whereNotNull('asset_name')->distinct()->get();
        $locations = Asset::whereNotNull('location')->pluck('location')->unique()->values();

        $itAssetsCount = $assets->filter(fn($a) => str_contains(strtolower($a->category ?? ''), 'it') || str_contains(strtolower($a->category ?? ''), 'computer') || str_contains(strtolower($a->category ?? ''), 'tech'))->count();
        $furnitureCount = $assets->filter(fn($a) => str_contains(strtolower($a->category ?? ''), 'furniture'))->count();
        $electricalCount = $assets->filter(fn($a) => str_contains(strtolower($a->category ?? ''), 'electric'))->count();
        $machineryCount = $assets->filter(fn($a) => str_contains(strtolower($a->category ?? ''), 'machin'))->count();

        $activeAssetsCount = $assets->filter(fn($a) => in_array(strtolower($a->status ?? ''), ['active', 'operational', 'good']))->count();
        $pendingRepairsCount = $assets->filter(fn($a) => in_array(strtolower($a->status ?? ''), ['pending repair', 'damaged', 'issue']))->count();

        $avgAssetAge = 0;
        if ($totalCount > 0) {
            $totalAgeDays = $assets->sum(fn($a) => \Carbon\Carbon::parse($a->created_at ?? $a->purchase_date ?? now())->diffInYears(now()));
            $avgAssetAge = round($totalAgeDays / $totalCount, 1);
        }

        $totalValue = $assets->sum('cost') ?? 0;
        $conditionKeys = ['good', 'fair', 'poor', 'damaged', 'critical', 'excellent', 'repair'];
        $conditionBreakdown = [];

        foreach ($conditionKeys as $key) {
            $count = $assets->filter(function ($a) use ($key) {
                $cond = strtolower($a->condition ?? '');
                return $key === 'critical' ? (str_contains($cond, 'critical') || str_contains($cond, 'repair')) : str_contains($cond, $key);
            })->count();

            $conditionBreakdown[$key] = [
                'count' => $count,
                'percent' => $totalCount > 0 ? round(($count / $totalCount) * 100) : 0,
            ];
        }

        $allocationByDept = collect([]);
        $allocationByLocation = collect([]);
        $lifecycleForecast = collect([]);

        return view('asset_analysis', compact(
            'assets', 'categories', 'conditions', 'departments', 'statuses', 'types', 'locations',
            'itAssetsCount', 'furnitureCount', 'electricalCount', 'machineryCount', 'activeAssetsCount',
            'pendingRepairsCount', 'avgAssetAge', 'totalValue', 'conditionBreakdown', 'allocationByDept',
            'allocationByLocation', 'lifecycleForecast'
        ));
    })->name('Asset_analysis');

    // Location Analysis
    Route::get('/analysis/location', function () {
        $assets = Asset::all();
        $totalAssetsCount = $assets->count();

        $categories = Asset::whereNotNull('category')->pluck('category')->unique()->values();
        $locations = Asset::whereNotNull('location')->pluck('location')->unique()->values();
        $statuses = Asset::whereNotNull('status')->pluck('status')->unique()->values();
        $conditions = Asset::whereNotNull('condition')->pluck('condition')->unique()->values();

        $totalActiveSites = $locations->count();
        $headOfficeCount = Asset::where(fn($q) => $q->whereRaw("LOWER(location) LIKE '%head office%'")->orWhereRaw("LOWER(location) LIKE '%hq%'")->orWhereRaw("LOWER(location) LIKE '%main%'"))->count();
        $branchAssetsCount = Asset::whereNotNull('location')->where('location', '!=', '')
            ->whereRaw("LOWER(location) NOT LIKE '%head office%'")
            ->whereRaw("LOWER(location) NOT LIKE '%hq%'")
            ->whereRaw("LOWER(location) NOT LIKE '%main%'")
            ->whereRaw("LOWER(location) NOT LIKE '%remote%'")
            ->whereRaw("LOWER(location) NOT LIKE '%wfh%'")
            ->whereRaw("LOWER(location) NOT LIKE '%home%'")
            ->count();

        $remoteAssetsCount = Asset::where(fn($q) => $q->whereRaw("LOWER(location) LIKE '%remote%'")->orWhereRaw("LOWER(location) LIKE '%wfh%'")->orWhereRaw("LOWER(location) LIKE '%home%'")->orWhereRaw("LOWER(location) LIKE '%field%'"))->count();

        $locationCounts = Asset::select('location', DB::raw('count(*) as total'))
                                ->whereNotNull('location')
                                ->where('location', '!=', '')
                                ->groupBy('location')
                                ->orderByDesc('total')
                                ->get();

        $topLocationRow = $locationCounts->first();
        $topLocation = $topLocationRow ? $topLocationRow->location : 'N/A';
        $topLocationCount = $topLocationRow ? $topLocationRow->total : 0;
        $unassignedCount = Asset::whereNull('location')->orWhere('location', '')->count();

        $distributionBySite = $locationCounts->map(function ($item) use ($totalAssetsCount) {
            return (object) [
                'name' => $item->location,
                'location' => $item->location,
                'site' => $item->location,
                'count' => $item->total,
                'total' => $item->total,
                'percentage' => $totalAssetsCount > 0 ? round(($item->total / $totalAssetsCount) * 100, 1) : 0,
                'percent' => $totalAssetsCount > 0 ? round(($item->total / $totalAssetsCount) * 100, 1) : 0,
            ];
        });

        $departments = collect([]);
        $locationBreakdown = collect([]);

        return view('location_analysis', compact(
            'assets', 'categories', 'locations', 'statuses', 'conditions', 'departments',
            'locationBreakdown', 'distributionBySite', 'totalActiveSites', 'headOfficeCount',
            'branchAssetsCount', 'remoteAssetsCount', 'topLocation', 'topLocationCount',
            'unassignedCount', 'totalAssetsCount'
        ));
    })->name('location_analysis');

    // Log History
    Route::get('/logs', function () {
        $logs = collect([]);
        if (Schema::hasTable('activity_logs')) {
            $logs = DB::table('activity_logs')->latest()->get();
        } elseif (Schema::hasTable('logs')) {
            $logs = DB::table('logs')->latest()->get();
        }
        return view('log_history', compact('logs'));
    })->name('log_history');

    // Damage Queue & Reporting
    Route::get('/damage-queue', [DamageReportController::class, 'index'])->name('damage_queue');
    Route::post('/damage-queue/report', [DamageReportController::class, 'store'])->name('report_damage.store');
    Route::post('/damage-queue/report-issue', [DamageReportController::class, 'store'])->name('report_issue');

    // Tag Generator & Bulk Actions
    Route::get('/tags/next-number', [TagGeneratorController::class, 'getNextNumber'])->name('tags.next_number');
    Route::post('/tags/bulk-generate', [TagGeneratorController::class, 'storeBulkTags'])->name('generate_bulk_tags');

    /*
    |--------------------------------------------------------------------------
    | Modal & API Endpoint Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        Route::get('/assets/by-type', [TagGeneratorController::class, 'getAssetsByType'])->name('assets.by_type');
        Route::get('/assets/tracking/{trackingNumber}', [TagGeneratorController::class, 'getAssetByTracking'])->name('assets.get_by_tracking');
        Route::put('/assets/{id}', [TagGeneratorController::class, 'updateAsset'])->name('assets.update');
        Route::delete('/assets/{id}', [TagGeneratorController::class, 'destroyAsset'])->name('assets.destroy');
    });

});