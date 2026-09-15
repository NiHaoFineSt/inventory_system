<?php

namespace App\Services\Analytics;

use App\Models\Asset;
use Illuminate\Http\Request;

class LocationAnalyticsService
{
    public function build(Request $request): array
    {
        $assets = Asset::all();
        $totalAssetsCount = $assets->count();

        $processed = $assets->map(function (Asset $asset) {
            $department = trim($asset->location ?? '');
            $isUnassigned = $department === '' || strtolower($department) === 'storage';

            $asset->display_location = $isUnassigned ? 'Storage' : $department;
            $asset->display_department = $isUnassigned ? 'Storage' : $department;
            $asset->display_assigned_to = $asset->assigned_to ?: ($isUnassigned ? 'Unassigned' : $department);
            $asset->display_status = $isUnassigned ? 'Inactive' : 'Active';

            return $asset;
        });

        $filtered = $processed
            ->when($request->filled('location') && $request->location !== 'All', fn ($c) => $c->where('display_location', $request->location))
            ->when($request->filled('status') && $request->status !== 'All', fn ($c) => $c->where('display_status', $request->status));

        $siteCounts = $processed->groupBy('display_department')
            ->map(fn ($group, $deptName) => (object) [
                'department_name' => $deptName,
                'count' => $group->count(),
                'percentage' => $totalAssetsCount > 0 ? round(($group->count() / $totalAssetsCount) * 100, 1) : 0,
            ])->values();

        return [
            'assets' => $filtered,
            'distributionBySite' => $siteCounts,
            'categories' => Asset::whereNotNull('category')->pluck('category')->unique()->values(),
            'locations' => $processed->pluck('display_location')->unique()->values(),
            'statuses' => ['Active', 'Inactive'],
            'totalActiveSites' => $siteCounts->where('department_name', '!=', 'Storage')->count(),
            'headOfficeCount' => $processed->where('display_location', 'Head Office')->count(),
            'branchAssetsCount' => $processed->whereNotIn('display_location', ['Head Office', 'Storage'])->count(),
            'remoteAssetsCount' => $processed->filter(fn ($a) => str_contains(strtolower($a->display_location), 'remote') || str_contains(strtolower($a->display_location), 'wfh'))->count(),
            'unassignedCount' => $processed->where('display_status', 'Inactive')->count(),
            'totalAssetsCount' => $totalAssetsCount,
        ];
    }
}
