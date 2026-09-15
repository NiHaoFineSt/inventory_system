<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function assignAssets(Request $request)
    {
        $validated = $request->validate([
            'asset_name'    => 'required|string',
            'category'      => 'required|string',
            'department'    => 'required|string',
            'quantity'      => 'required|integer|min:1',
            'employee_name' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // Find unassigned assets matching criteria
            $assetIds = Asset::where('asset_name', $validated['asset_name'])
                ->where('category', $validated['category'])
                ->where(function ($query) {
                    $query->whereNull('assigned_to')
                          ->orWhere('assigned_to', '');
                })
                ->take($validated['quantity'])
                ->pluck('id');

            // Check availability
            if ($assetIds->count() < $validated['quantity']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Not enough unassigned assets available.'
                ], 422);
            }

            // Perform single bulk update
            Asset::whereIn('id', $assetIds)->update([
                'location'    => $validated['department'],
                'assigned_to' => $validated['employee_name'] ?? null,
                'status'      => 'Assigned',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Assets successfully assigned.'
            ]);
        });
    }
}