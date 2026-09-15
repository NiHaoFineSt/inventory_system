<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetApiController extends Controller
{
    public function __construct(private readonly AssetService $assetService) {}

    public function byType(Request $request): JsonResponse
    {
        $assets = Asset::where('asset_name', $request->query('asset_name'))
            ->where('category', $request->query('category'))
            ->get();

        $pendingCount = $assets->filter(fn (Asset $a) => $a->condition?->value !== 'Good')->count();

        return response()->json([
            'success' => true,
            'assets' => $assets,
            'pending_count' => $pendingCount,
        ]);
    }

    public function byTracking(string $trackingNumber): JsonResponse
    {
        if (str_contains($trackingNumber, '|')) {
            $trackingNumber = trim(explode('|', $trackingNumber)[0]);
        }

        $asset = Asset::where('tracking_number', $trackingNumber)->first();

        if (! $asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
        }

        return response()->json([
            'success' => true,
            'asset' => [
                'id' => $asset->id,
                'tracking_number' => $asset->tracking_number,
                'asset_name' => $asset->asset_name,
                'category' => $asset->category,
                'location' => $asset->location,
                'assignment_status' => $asset->assigned_to ? 'Assigned' : 'Unassigned',
                'status' => $asset->status?->value,
                'condition' => $asset->condition?->value,
            ],
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $asset = $this->assetService->updateAsset($actor, $asset, $request->validated());

        return response()->json(['success' => true, 'message' => 'Asset updated successfully!', 'asset' => $asset]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->assetService->deleteAsset($actor, $asset);

        return response()->json(['success' => true, 'message' => 'Asset deleted successfully!']);
    }
}
