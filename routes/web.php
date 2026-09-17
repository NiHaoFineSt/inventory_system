<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Http\Controllers\TagGeneratorController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\LogHistoryController;
use App\Http\Controllers\DamageQueueController;


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
        // Fetch active/unresolved damage reports
        $activeDamageReports = DB::table('damage_reports')
            ->whereNotIn('status', ['Resolved', 'Completed'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->keyBy(function ($item) {
                // Key by clean tracking number (e.g. "DSK-021")
                $cleanTracking = trim(explode('|', $item->tracking_number)[0]);
                return strtoupper($cleanTracking);
            });

        $assets = Asset::all()->map(function ($asset) use ($activeDamageReports) {
            $rawTracking = trim(explode('|', $asset->tracking_number)[0]);
            $cleanTracking = strtoupper($rawTracking);
            $stripped = str_replace('TRK-', '', $cleanTracking);
            $withTrk = 'TRK-' . $cleanTracking;

            // Match against active damage reports
            $report = $activeDamageReports->get($cleanTracking)
                ?? $activeDamageReports->get($stripped)
                ?? $activeDamageReports->get($withTrk);

            if ($report) {
                $asset->latest_damage_status = $report->status;
                $asset->latest_damage_issue = $report->issue_type ?? $report->description ?? 'Reported Issue';
                $asset->condition = 'Damaged';
                $asset->status = 'Pending Repair';
            }

            return $asset;
        });

        return view('asset_dashboard', compact('assets'));
    })->name('asset_dashboard');

    Route::get('/staff_dashboard', function () {
        $user = Auth::user();

        $reports = DB::table('damage_reports')
            ->where(function ($query) use ($user) {
                if (isset($user->id)) {
                    $query->where('user_id', $user->id);
                }
                if (!empty($user->name)) {
                    $query->orWhere('reported_by', $user->name);
                }
                if (!empty($user->email)) {
                    $query->orWhere('reported_by', $user->email);
                }
            })
            ->latest()
            ->get();

        $assignedAssets = Asset::where('location', 'LIKE', '%' . $user->name . '%')
            ->orWhere('location', 'LIKE', '%' . $user->email . '%')
            ->get();

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

    // Category Management Routes
    Route::post('/categories/store', function (Request $request) {
        $request->validate(['category_name' => 'required|string|max:255']);
        $categoryName = trim($request->category_name);

        Asset::create([
            'category'        => $categoryName,
            'asset_name'      => 'General ' . $categoryName,
            'tracking_number' => 'CAT-' . strtoupper(substr(md5(uniqid()), 0, 6)),
            'status'          => 'Inactive',
            'condition'       => 'Good',
            'location'        => 'Unassigned',
        ]);

        return response()->json(['success' => true, 'message' => 'Category added successfully.']);
    })->name('categories.store');

    Route::put('/categories/update', function (Request $request) {
        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        Asset::where('category', $request->old_name)
            ->update(['category' => trim($request->new_name)]);

        return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
    })->name('categories.update');

    Route::delete('/categories/delete', function (Request $request) {
        $request->validate(['category_name' => 'required|string']);

        Asset::where('category', $request->category_name)->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    })->name('categories.destroy');

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
    Route::get('/analysis/asset', function (Request $request) {
        $assets = Asset::all();

        $processedAssets = $assets->map(function ($asset) {
            $rawLocation = trim($asset->location ?? '');
            $dept = str_contains($rawLocation, '-') ? trim(explode('-', $rawLocation)[0]) : $rawLocation;

            $asset->display_department = (empty($dept) || strtolower($dept) === 'unassigned' || strtolower($dept) === 'storage') 
                ? 'Storage / Unassigned' 
                : $dept;

            return $asset;
        });

        $filteredAssets = $processedAssets
            ->when($request->filled('category') && $request->category !== 'All Categories', fn($c) => $c->where('category', $request->category))
            ->when($request->filled('condition') && $request->condition !== 'All Conditions', fn($c) => $c->where('condition', $request->condition))
            ->when($request->filled('department') && $request->department !== 'All Departments', fn($c) => $c->where('display_department', $request->department));

        $filteredTotal = $filteredAssets->count();

        $itAssetsCount = $filteredAssets->filter(fn($a) => str_contains(strtolower($a->category ?? ''), 'it') || str_contains(strtolower($a->category ?? ''), 'computer') || str_contains(strtolower($a->category ?? ''), 'hardware'))->count();
        $furnitureCount = $filteredAssets->filter(fn($a) => str_contains(strtolower($a->category ?? ''), 'furniture'))->count();

        $avgAssetAge = 0;
        if ($filteredTotal > 0) {
            $totalAgeYears = $filteredAssets->sum(function ($a) {
                $date = $a->created_at ?? $a->purchase_date ?? null;
                return $date ? \Carbon\Carbon::parse($date)->diffInYears(now()) : 0;
            });
            $avgAssetAge = round($totalAgeYears / $filteredTotal, 1);
        }

        $goodCount = $filteredAssets->filter(fn($a) => in_array(strtolower($a->condition ?? ''), ['good', 'excellent', 'new', 'active', 'operational']) || empty($a->condition))->count();
        $fairCount = $filteredAssets->filter(fn($a) => in_array(strtolower($a->condition ?? ''), ['fair', 'minor wear', 'moderate', 'used']))->count();
        $criticalCount = $filteredAssets->filter(fn($a) => in_array(strtolower($a->condition ?? ''), ['critical', 'poor', 'damaged', 'needs repair', 'broken']))->count();

        $conditionBreakdown = [
            'good'     => ['count' => $goodCount,     'percent' => $filteredTotal > 0 ? round(($goodCount / $filteredTotal) * 100) : 0],
            'fair'     => ['count' => $fairCount,     'percent' => $filteredTotal > 0 ? round(($fairCount / $filteredTotal) * 100) : 0],
            'critical' => ['count' => $criticalCount, 'percent' => $filteredTotal > 0 ? round(($criticalCount / $filteredTotal) * 100) : 0],
        ];

        $allocationByDept = $filteredAssets->groupBy('display_department')
            ->map(function ($group, $deptName) {
                return (object) [
                    'dept'        => $deptName,
                    'asset_count' => $group->count(),
                ];
            })->values();

        $lifecycleForecast = $filteredAssets->groupBy('category')
            ->map(function ($group, $catName) {
                $catTotal = $group->count();

                $b0_2 = $group->filter(function ($a) {
                    $date = $a->created_at ?? $a->purchase_date ?? null;
                    return $date ? \Carbon\Carbon::parse($date)->diffInYears(now()) <= 2 : true;
                })->count();

                $b3_5 = $group->filter(function ($a) {
                    $date = $a->created_at ?? $a->purchase_date ?? null;
                    if (!$date) return false;
                    $age = \Carbon\Carbon::parse($date)->diffInYears(now());
                    return $age >= 3 && $age <= 5;
                })->count();

                $b5plus = $group->filter(function ($a) {
                    $date = $a->created_at ?? $a->purchase_date ?? null;
                    return $date ? \Carbon\Carbon::parse($date)->diffInYears(now()) > 5 : false;
                })->count();

                return (object) [
                    'category'    => $catName ?: 'Uncategorized',
                    'total_units' => $catTotal,
                    'age_0_2'     => $b0_2,
                    'age_3_5'     => $b3_5,
                    'age_5_plus'  => $b5plus,
                ];
            })->values();

        $categories = Asset::whereNotNull('category')->pluck('category')->unique()->values();
        $conditions = ['Good', 'Fair', 'Critical'];
        $departments = $processedAssets->pluck('display_department')->unique()->values();

        return view('asset_analysis', compact(
            'assets', 'categories', 'conditions', 'departments',
            'itAssetsCount', 'furnitureCount', 'avgAssetAge',
            'conditionBreakdown', 'allocationByDept', 'lifecycleForecast'
        ));
    })->name('Asset_analysis');

    // Log History
    Route::get('/logs', [LogHistoryController::class, 'index'])->name('log_history');

    // Location Analysis
    Route::get('/analysis/location', function (Request $request) {
        $assets = Asset::all();
        $totalAssetsCount = $assets->count();

        $processedAssets = $assets->map(function ($asset) {
            $rawLocation = trim($asset->location ?? '');

            $department = '';
            $assignedPerson = '';

            if (str_contains($rawLocation, '-')) {
                $parts = explode('-', $rawLocation, 2);
                $department = trim($parts[0]);
                $assignedPerson = trim($parts[1]);
            } else {
                $department = $rawLocation;
            }

            $isUnassigned = empty($department) || strtolower($department) === 'unassigned' || strtolower($department) === 'storage';

            $finalLocation = $isUnassigned ? 'Storage' : $department;
            $finalStatus = $isUnassigned ? 'Inactive' : 'Active';

            if (!empty($assignedPerson)) {
                $assignedTo = $assignedPerson;
            } elseif (!$isUnassigned) {
                $assignedTo = $department;
            } else {
                $assignedTo = 'Unassigned';
            }

            $asset->display_location = $finalLocation;
            $asset->display_department = $isUnassigned ? 'Storage' : $department;
            $asset->display_assigned_to = $assignedTo;
            $asset->display_status = $finalStatus;

            return $asset;
        });

        $filteredAssets = $processedAssets->when($request->filled('location') && $request->location !== 'All', function ($collection) use ($request) {
            return $collection->where('display_location', $request->location);
        })->when($request->filled('status') && $request->status !== 'All', function ($collection) use ($request) {
            return $collection->where('display_status', $request->status);
        });

        $siteCounts = $processedAssets->groupBy('display_department')
            ->map(function ($group, $deptName) use ($totalAssetsCount) {
                $count = $group->count();
                return (object) [
                    'department_name' => $deptName,
                    'location'        => $deptName,
                    'name'            => $deptName,
                    'site'            => $deptName,
                    'count'           => $count,
                    'percentage'      => $totalAssetsCount > 0 ? round(($count / $totalAssetsCount) * 100, 1) : 0,
                ];
            })->values();

        $categories = Asset::whereNotNull('category')->pluck('category')->unique()->values();
        $locations = $processedAssets->pluck('display_location')->unique()->values();
        $statuses = ['Active', 'Inactive'];

        $totalActiveSites = $siteCounts->where('department_name', '!=', 'Storage')->count();
        $headOfficeCount = $processedAssets->where('display_location', 'Head Office')->count();
        $branchAssetsCount = $processedAssets->whereNotIn('display_location', ['Head Office', 'Storage'])->count();
        $remoteAssetsCount = $processedAssets->filter(fn($a) => str_contains(strtolower($a->display_location), 'remote') || str_contains(strtolower($a->display_location), 'wfh'))->count();
        $unassignedCount = $processedAssets->where('display_status', 'Inactive')->count();

        return view('location_analysis', [
            'assets'             => $filteredAssets,
            'distributionBySite' => $siteCounts,
            'categories'         => $categories,
            'locations'          => $locations,
            'statuses'           => $statuses,
            'totalActiveSites'   => $totalActiveSites,
            'headOfficeCount'    => $headOfficeCount,
            'branchAssetsCount'  => $branchAssetsCount,
            'remoteAssetsCount'  => $remoteAssetsCount,
            'unassignedCount'    => $unassignedCount,
            'totalAssetsCount'   => $totalAssetsCount,
        ]);
    })->name('location_analysis');

    // Damage Queue & Reporting
    Route::get('/damage-queue', [DamageReportController::class, 'index'])->name('damage_queue');
    Route::post('/damage-queue/report', [DamageReportController::class, 'store'])->name('report_damage.store');
    Route::post('/damage-queue/report-issue', [DamageReportController::class, 'store'])->name('report_issue');
    Route::patch('/damage-queue/{id}/resolve', [DamageQueueController::class, 'resolve'])->name('damage_queue.resolve');

    // Tag Generator & Bulk Actions
    Route::get('/tags/next-number', [TagGeneratorController::class, 'getNextNumber'])->name('tags.next_number');
    Route::post('/tags/bulk-generate', [TagGeneratorController::class, 'storeBulkTags'])->name('generate_bulk_tags');

    Route::prefix('api')->group(function () {
        Route::get('/assets/by-type', [TagGeneratorController::class, 'getAssetsByType'])->name('assets.by_type');
        Route::get('/assets/tracking/{trackingNumber}', [TagGeneratorController::class, 'getAssetByTracking'])->name('assets.get_by_tracking');
        Route::put('/assets/{id}', [TagGeneratorController::class, 'updateAsset'])->name('assets.update');
        Route::delete('/assets/{id}', [TagGeneratorController::class, 'destroyAsset'])->name('assets.destroy');
    });

});