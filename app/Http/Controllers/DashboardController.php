<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Fetch raw assets and damage reports
        $rawAssets = DB::table('assets')->get();
        $damageReports = DB::table('damage_reports')->get();

        // 2. Group assets by unique core tracking code to eliminate duplicates like "TRK-DSK-020" vs "DSK-020"
        $uniqueAssets = $rawAssets->groupBy(function ($asset) {
            $code = trim(explode('|', $asset->tracking_number ?? '')[0]);
            return strtolower(str_replace('TRK-', '', $code));
        });

        // 3. Map unique assets and merge status/damage data
        $assets = $uniqueAssets->map(function ($group) use ($damageReports) {
            // Pick the primary row (preferring non-TRK version if present)
            $asset = $group->firstWhere(function ($item) {
                return !str_contains($item->tracking_number, 'TRK-');
            }) ?? $group->first();

            $rawCode = trim(explode('|', $asset->tracking_number ?? '')[0]);
            $cleanCode = strtolower(str_replace('TRK-', '', $rawCode));

            // Match damage reports across both prefixed and non-prefixed codes
            $reports = $damageReports->filter(function ($report) use ($cleanCode) {
                if (empty($report->tracking_number)) return false;

                $reportCode = strtolower(trim(explode('|', $report->tracking_number)[0]));
                $cleanReportCode = str_replace('TRK-', '', $reportCode);

                return $cleanReportCode === $cleanCode;
            });

            // Get the latest damage report
            $latestReport = $reports->sortByDesc('id')->first();

            // Set normalized status properties for blade/JS consumption
            if ($latestReport && strtolower($latestReport->status) !== 'resolved') {
                $asset->status = 'Pending Repair';
                $asset->condition = 'Damaged';
                $asset->latest_damage_status = $latestReport->status;
                $asset->latest_damage_issue = $latestReport->issue_type ?? $latestReport->description ?? '';
            } else {
                $asset->latest_damage_status = $latestReport->status ?? $asset->status ?? '';
                $asset->latest_damage_issue = $latestReport->issue_type ?? $latestReport->description ?? '';
            }

            return $asset;
        })->values();

        return view('asset_dashboard', compact('assets'));
    }
}