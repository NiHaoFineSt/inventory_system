<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DamageQueueController extends Controller
{
    /**
     * Display all damage reports in the queue.
     */
    public function index()
    {
        $damagedAssets = DB::table('damage_reports')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('damage_queue', [
            'damagedAssets' => $damagedAssets,
            'reports'       => $damagedAssets,
        ]);
    }

    /**
     * Resolve a damage report and restore the main asset condition.
     */
    public function resolve($id)
    {
        $user = Auth::user();

        DB::transaction(function () use ($id, $user) {
            $report = DB::table('damage_reports')->where('id', $id)->first();

            if (!$report) {
                return;
            }

            // 1. Update report status to Resolved
            DB::table('damage_reports')
                ->where('id', $id)
                ->update([
                    'status'     => 'Resolved',
                    'updated_at' => now(),
                ]);

            // 2. Extract clean tracking code (strips delimiters like |)
            $cleanTracking = trim(explode('|', $report->tracking_number)[0]);

            // 3. Reset main asset condition back to Good / Active
            DB::table('assets')
                ->where('tracking_number', $cleanTracking)
                ->orWhere('tracking_number', 'TRK-' . $cleanTracking)
                ->orWhere('tracking_number', str_replace('TRK-', '', $cleanTracking))
                ->update([
                    'condition'  => 'Good',
                    'status'     => 'Active',
                    'updated_at' => now(),
                ]);

            // 4. Log the resolution activity
            DB::table('activity_logs')->insert([
                'user_id'         => $user->id ?? null,
                'action'          => 'RESOLVED',
                'asset_name'      => $report->asset_name,
                'tracking_number' => $cleanTracking,
                'performed_by'    => $user->name ?? $user->email ?? 'System User',
                'description'     => "Resolved damage issue: {$report->issue_type}",
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });

        return redirect()->route('damage_queue')->with('success', 'Damage report resolved and asset restored to Good condition!');
    }
}