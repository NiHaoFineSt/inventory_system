<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetDirectoryController extends Controller
{
    public function index()
    {
        // Fetch categories for the filter dropdown
        $categories = Asset::distinct()->pluck('category')->filter();

        // Fetch types for the filter dropdown
        $types = Asset::select('asset_name', 'category')->distinct()->get();

        // Updated summary query supporting status & assigned_to checks
        $summaryData = DB::table('assets')
            ->selectRaw("
                category, 
                asset_name, 
                COUNT(*) as total_count, 
                SUM(CASE WHEN (assigned_to IS NOT NULL AND assigned_to != '') OR status = 'Assigned' THEN 1 ELSE 0 END) as assigned_count,
                SUM(CASE WHEN (assigned_to IS NULL OR assigned_to = '') AND (status IS NULL OR status != 'Assigned') THEN 1 ELSE 0 END) as unassigned_count,
                SUM(CASE WHEN `condition` != 'Good' THEN 1 ELSE 0 END) as pending_count
            ")
            ->groupBy('category', 'asset_name')
            ->get();

        // Pass variables to the blade view
        return view('asset_directory', compact('categories', 'types', 'summaryData'));
    }

    public function getAssetsByType(Request $request)
    {
        $assets = Asset::where('asset_name', $request->type)
            ->where('category', $request->category)
            ->get();

        $pendingCount = $assets->where('condition', '!=', 'Good')->count();

        return response()->json([
            'status' => 'success',
            'assets' => $assets,
            'pending_count' => $pendingCount
        ]);
    }

    public function updateAsset(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $asset->update($request->only(['tracking_number', 'condition']));

        return response()->json(['status' => 'success']);
    }

    public function destroyAsset($id)
    {
        Asset::findOrFail($id)->delete();
        return response()->json(['status' => 'success']);
    }
}