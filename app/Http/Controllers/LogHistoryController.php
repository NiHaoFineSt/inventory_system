<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogHistoryController extends Controller
{
    public function index()
    {
        // 1. Fetch damage reports
        $damageLogs = DB::table('damage_reports')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'created_at'      => $item->created_at ?? now()->toDateTimeString(),
                    'action'          => 'REPORTED',
                    'asset_name'      => $item->asset_name ?? '-',
                    'tracking_number' => $item->tracking_number ?? '-',
                    'performed_by'    => $item->reported_by ?? 'Staff',
                    'description'     => 'Issue (' . ($item->issue_type ?? 'Damage') . '): ' . ($item->description ?? ''),
                ];
            });

        // 2. Fetch assets table (or activity_logs if you have one)
        $assetLogs = DB::table('assets')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'created_at'      => $item->created_at ?? now()->toDateTimeString(),
                    'action'          => 'ADDED',
                    'asset_name'      => $item->asset_name ?? $item->name ?? '-',
                    'tracking_number' => $item->tracking_number ?? $item->qr_code ?? '-',
                    'performed_by'    => $item->created_by ?? 'System Admin',
                    'description'     => 'Asset recorded in database',
                ];
            });

        // 3. Merge both collections and sort by newest first
        $logs = $damageLogs->concat($assetLogs)->sortByDesc('created_at');

        return view('log_history', ['logs' => $logs]);
    }
}