<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationAnalysisController extends Controller
{
    public function index(Request $request)
    {
        // 1. Dynamic Dropdown Lists
        $locations = Asset::distinct()->whereNotNull('location')->where('location', '!=', '')->pluck('location');
        $statuses = Asset::distinct()->whereNotNull('status')->where('status', '!=', '')->pluck('status');

        // 2. Metrics Grid
        $totalActiveSites = $locations->count();
        $headOfficeCount = Asset::where('location', 'Head Office')->count();
        $branchAssetsCount = Asset::where('location', 'LIKE', 'Branch%')->count();
        $remoteAssetsCount = Asset::where('location', 'Remote')->count();

        // 3. Asset Distribution by Site (Count & Percentage)
        $totalAssets = Asset::count();
        $distributionBySite = Asset::select('location', DB::raw('count(*) as count'))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->get()
            ->map(function ($item) use ($totalAssets) {
                $item->percentage = $totalAssets > 0 ? round(($item->count / $totalAssets) * 100, 1) : 0;
                return $item;
            });

        // 4. Location Asset Directory (Filtered by Request)
        $assetQuery = Asset::query();

        if ($request->filled('location') && $request->location !== 'All') {
            $assetQuery->where('location', $request->location);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $assetQuery->where('status', $request->status);
        }

        $assets = $assetQuery->latest()->get();

        return view('location_analysis', compact(
            'locations',
            'statuses',
            'totalActiveSites',
            'headOfficeCount',
            'branchAssetsCount',
            'remoteAssetsCount',
            'distributionBySite',
            'assets'
        ));
    }
}