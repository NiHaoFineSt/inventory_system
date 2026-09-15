<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetAnalysisController extends Controller
{
    public function index(Request $request)
    {
        // 1. Build Base Filtered Query
        $query = Asset::query();

        if ($request->filled('category') && $request->category !== 'All Categories') {
            $query->where('category', $request->category);
        }
        if ($request->filled('condition') && $request->condition !== 'All Conditions') {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('department') && $request->department !== 'All Departments') {
            $query->where('location', $request->department);
        }

        // 2. Fetch Filter Options
        $categories = Asset::distinct()->pluck('category')->filter();
        $conditions = Asset::distinct()->pluck('condition')->filter();
        $departments = Asset::distinct()->pluck('location')->filter();

        // 3. Top Metrics
        $itAssetsCount = (clone $query)->whereIn('category', ['IT Hardware', 'IT Equipment', 'Electronics'])->count();
        $furnitureCount = (clone $query)->where('category', 'Furniture')->count();
        
        // Calculate Average Asset Age in Years (using created_at)
        $avgAgeDays = (clone $query)->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')->value('avg_days') ?? 0;
        $avgAssetAge = round($avgAgeDays / 365, 1);

        // 4. Asset Condition Breakdown
        $totalAssets = (clone $query)->count();
        $goodCount = (clone $query)->where('condition', 'Good')->count();
        $fairCount = (clone $query)->where('condition', 'Fair')->count();
        $criticalCount = (clone $query)->whereIn('condition', ['Poor', 'Damaged', 'Critical'])->count();

        $conditionBreakdown = [
            'good' => ['count' => $goodCount, 'percent' => $totalAssets ? round(($goodCount / $totalAssets) * 100) : 0],
            'fair' => ['count' => $fairCount, 'percent' => $totalAssets ? round(($fairCount / $totalAssets) * 100) : 0],
            'critical' => ['count' => $criticalCount, 'percent' => $totalAssets ? round(($criticalCount / $totalAssets) * 100) : 0],
        ];

        // 5. Allocation by Department / Location
        $allocationByDept = (clone $query)
            ->selectRaw("COALESCE(NULLIF(location, ''), 'Unassigned / Storage') as dept, COUNT(*) as asset_count")
            ->groupBy('dept')
            ->orderByDesc('asset_count')
            ->get();

        // 6. Lifecycle & Replacement Forecast Table
        $lifecycleForecast = (clone $query)
            ->selectRaw("
                category,
                COUNT(*) as total_units,
                SUM(CASE WHEN DATEDIFF(NOW(), created_at) <= 730 THEN 1 ELSE 0 END) as age_0_2,
                SUM(CASE WHEN DATEDIFF(NOW(), created_at) > 730 AND DATEDIFF(NOW(), created_at) <= 1825 THEN 1 ELSE 0 END) as age_3_5,
                SUM(CASE WHEN DATEDIFF(NOW(), created_at) > 1825 THEN 1 ELSE 0 END) as age_5_plus
            ")
            ->groupBy('category')
            ->get();

        return view('asset_analysis', compact(
            'categories',
            'conditions',
            'departments',
            'itAssetsCount',
            'furnitureCount',
            'avgAssetAge',
            'conditionBreakdown',
            'allocationByDept',
            'lifecycleForecast'
        ));
    }
}