<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignAssetsRequest;
use App\Models\Asset;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class AssetDirectoryController extends Controller
{
    public function __construct(private readonly AssetService $assetService) {}

    public function index(): View
    {
        $categories = Asset::whereNotNull('category')->distinct()->pluck('category');
        $types = Asset::select('asset_name', 'category')->distinct()->get();

        $summaryData = DB::table('assets')
            ->selectRaw('
                category,
                asset_name,
                COUNT(*) as total_count,
                SUM(CASE WHEN assigned_to IS NOT NULL AND assigned_to != \'\' THEN 1 ELSE 0 END) as assigned_count,
                SUM(CASE WHEN assigned_to IS NULL OR assigned_to = \'\' THEN 1 ELSE 0 END) as unassigned_count,
                SUM(CASE WHEN condition != \'Good\' THEN 1 ELSE 0 END) as pending_count
            ')
            ->groupBy('category', 'asset_name')
            ->get();

        return view('asset_directory', compact('categories', 'types', 'summaryData'));
    }

    public function assign(AssignAssetsRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        try {
            $result = $this->assetService->assign($actor, $request->validated());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned {$result['count']} unit(s) to {$result['location']}.",
        ]);
    }
}
