<?php

namespace App\Services\Analytics;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AssetAnalyticsService
{
    public function build(Request $request): array
    {
        $assets = Asset::all();

        $processed = $assets->map(function (Asset $asset) {
            $department = trim($asset->location ?? '');
            $asset->display_department = ($department === '' || strtolower($department) === 'storage')
                ? 'Storage / Unassigned'
                : $department;

            return $asset;
        });

        $filtered = $processed
            ->when($request->filled('category') && $request->category !== 'All Categories', fn ($c) => $c->where('category', $request->category))
            ->when($request->filled('condition') && $request->condition !== 'All Conditions', fn ($c) => $c->where('condition', $request->condition))
            ->when($request->filled('department') && $request->department !== 'All Departments', fn ($c) => $c->where('display_department', $request->department));

        $total = $filtered->count();

        $itAssetsCount = $filtered->filter(fn ($a) => str_contains(strtolower($a->category ?? ''), 'it') || str_contains(strtolower($a->category ?? ''), 'computer') || str_contains(strtolower($a->category ?? ''), 'hardware'))->count();
        $furnitureCount = $filtered->filter(fn ($a) => str_contains(strtolower($a->category ?? ''), 'furniture'))->count();

        $avgAssetAge = $total > 0
            ? round($filtered->sum(fn ($a) => $this->ageInYears($a)) / $total, 1)
            : 0;

        $goodCount = $filtered->filter(fn ($a) => in_array($a->condition?->value, ['Good'], true))->count();
        $fairCount = $filtered->filter(fn ($a) => in_array($a->condition?->value, ['Fair'], true))->count();
        $criticalCount = $filtered->filter(fn ($a) => in_array($a->condition?->value, ['Damaged'], true))->count();

        $conditionBreakdown = [
            'good' => ['count' => $goodCount, 'percent' => $total > 0 ? round(($goodCount / $total) * 100) : 0],
            'fair' => ['count' => $fairCount, 'percent' => $total > 0 ? round(($fairCount / $total) * 100) : 0],
            'critical' => ['count' => $criticalCount, 'percent' => $total > 0 ? round(($criticalCount / $total) * 100) : 0],
        ];

        $allocationByDept = $filtered->groupBy('display_department')
            ->map(fn ($group, $deptName) => (object) ['dept' => $deptName, 'asset_count' => $group->count()])
            ->values();

        $lifecycleForecast = $filtered->groupBy('category')
            ->map(function ($group, $catName) {
                return (object) [
                    'category' => $catName ?: 'Uncategorized',
                    'total_units' => $group->count(),
                    'age_0_2' => $group->filter(fn ($a) => $this->ageInYears($a) <= 2)->count(),
                    'age_3_5' => $group->filter(fn ($a) => $this->ageInYears($a) >= 3 && $this->ageInYears($a) <= 5)->count(),
                    'age_5_plus' => $group->filter(fn ($a) => $this->ageInYears($a) > 5)->count(),
                ];
            })->values();

        return [
            'assets' => $assets,
            'categories' => Asset::whereNotNull('category')->pluck('category')->unique()->values(),
            'conditions' => ['Good', 'Fair', 'Damaged'],
            'departments' => $processed->pluck('display_department')->unique()->values(),
            'itAssetsCount' => $itAssetsCount,
            'furnitureCount' => $furnitureCount,
            'avgAssetAge' => $avgAssetAge,
            'conditionBreakdown' => $conditionBreakdown,
            'allocationByDept' => $allocationByDept,
            'lifecycleForecast' => $lifecycleForecast,
        ];
    }

    private function ageInYears(Asset $asset): int
    {
        $date = $asset->created_at;

        return $date ? Carbon::parse($date)->diffInYears(now()) : 0;
    }
}
