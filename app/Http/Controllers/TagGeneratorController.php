<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagGeneratorController extends Controller
{
    /**
     * Fetch all individual assets matching asset_name and category.
     */
    public function getAssetsByType(Request $request)
    {
        try {
            $assetName = $request->query('asset_name');
            $category  = $request->query('category');

            $query = Asset::query();

            if ($assetName) {
                $query->where('asset_name', $assetName);
            }

            if ($category && strtolower($category) !== 'all') {
                $query->where('category', $category);
            }

            $assets = $query->orderBy('tracking_number', 'asc')->get();

            return response()->json([
                'success' => true,
                'assets'  => $assets
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching assets by type: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update individual asset details (location, status, condition, name).
     */
    public function updateAsset(Request $request, $id)
    {
        try {
            $asset = Asset::find($id);
            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
            }

            $validated = $request->validate([
                'asset_name' => 'required|string',
                'location'   => 'nullable|string',
                'status'     => 'required|string',
                'condition'  => 'required|string',
            ]);

            $asset->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Asset updated successfully!',
                'asset'   => $asset
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating asset: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an individual asset record.
     */
    public function destroyAsset($id)
    {
        try {
            $asset = Asset::find($id);
            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
            }

            $asset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully!'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting asset: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the next available integer for a given tracking prefix.
     */
    public function getNextNumber(Request $request)
    {
        try {
            $prefix = trim($request->query('prefix', ''));

            if (empty($prefix)) {
                return response()->json(['success' => true, 'next_number' => 1], 200);
            }

            $existingAssets = Asset::where('tracking_number', 'LIKE', $prefix . '%')->pluck('tracking_number');

            $maxNum = 0;
            foreach ($existingAssets as $tracking) {
                if (preg_match('/(\d+)$/', $tracking, $matches)) {
                    $val = (int)$matches[1];
                    if ($val > $maxNum) {
                        $maxNum = $val;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'next_number' => $maxNum + 1
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error calculating next tracking number: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate bulk assets and save to database.
     */
    public function storeBulkTags(Request $request)
    {
        $validated = $request->validate([
            'category'        => 'required|string',
            'asset_name'      => 'required|string',
            'prefix'          => 'required|string',
            'starting_number' => 'required|integer|min:1',
            'quantity'        => 'required|integer|min:1',
            'location'        => 'nullable|string',
        ]);

        $createdAssets = [];
        $prefix = strtoupper(trim($validated['prefix']));
        $startNum = (int)$validated['starting_number'];
        $quantity = (int)$validated['quantity'];
        $location = $validated['location'] ?? 'Unassigned';

        DB::beginTransaction();
        try {
            $currentNum = $startNum;
            $createdCount = 0;

            while ($createdCount < $quantity) {
                $formattedNum = str_pad($currentNum, 3, '0', STR_PAD_LEFT);
                $trackingNumber = $prefix . $formattedNum;

                if (Asset::where('tracking_number', $trackingNumber)->exists()) {
                    $currentNum++;
                    continue;
                }

                $asset = Asset::create([
                    'category'        => $validated['category'],
                    'asset_name'      => $validated['asset_name'],
                    'tracking_number' => $trackingNumber,
                    'unit_number'     => $formattedNum,
                    'status'          => 'Active',
                    'condition'       => 'Good',
                    'location'        => $location,
                    'qr_code_payload' => $trackingNumber,
                ]);

                $createdAssets[] = $asset;
                $createdCount++;
                $currentNum++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => "Successfully created {$quantity} assets.",
                'data'    => $createdAssets
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing bulk tags: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate asset tags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch live asset details by tracking number API.
     */
    public function getAssetByTracking($trackingNumber)
    {
        try {
            if (str_contains($trackingNumber, '|')) {
                $trackingNumber = trim(explode('|', $trackingNumber)[0]);
            }

            $asset = Asset::where('tracking_number', $trackingNumber)->first();

            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
            }

            return response()->json([
                'success' => true,
                'asset' => [
                    'id'                => $asset->id,
                    'tracking_number'   => $asset->tracking_number,
                    'asset_name'        => $asset->asset_name,
                    'category'          => $asset->category,
                    'location'          => $asset->location,
                    'assignment_status' => ($asset->location && strtolower($asset->location) !== 'unassigned') ? 'Assigned' : 'Unassigned',
                    'status'            => $asset->status,
                    'condition'         => $asset->condition
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching asset by tracking code: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}