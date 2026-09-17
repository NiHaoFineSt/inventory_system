<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DamageReportController extends Controller
{
    /**
     * Redirect index calls to DamageQueueController or render queue view.
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
     * Store a new damage report submitted by users.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string',
            'asset_name'      => 'required|string',
            'issue_type'      => 'required|string',
        ]);

        $user = Auth::user();

        // Extract clean tracking number
        $cleanTrackingNumber = trim(explode('|', $request->input('tracking_number'))[0]);

        $description = $request->input('description') 
                    ?? $request->input('issue_description') 
                    ?? '';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('damage_photos', 'public');
        } elseif ($request->hasFile('damage_photo')) {
            $imagePath = $request->file('damage_photo')->store('damage_photos', 'public');
        }

        $reporterName = $user->name ?? $user->email ?? 'System User';

        DB::transaction(function () use ($request, $user, $reporterName, $description, $imagePath, $cleanTrackingNumber) {
            // 1. Create damage_reports entry
            DB::table('damage_reports')->insert([
                'user_id'         => $user->id ?? null,
                'reported_by'     => $reporterName,
                'tracking_number' => $cleanTrackingNumber,
                'asset_name'      => $request->asset_name,
                'issue_type'      => $request->issue_type,
                'description'     => $description,
                'image'           => $imagePath,
                'status'          => 'Pending Inspection',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // 2. Mark Asset as Damaged / Pending Repair
            DB::table('assets')
                ->where('tracking_number', $cleanTrackingNumber)
                ->orWhere('tracking_number', 'TRK-' . $cleanTrackingNumber)
                ->orWhere('tracking_number', str_replace('TRK-', '', $cleanTrackingNumber))
                ->update([
                    'condition'  => 'Damaged',
                    'status'     => 'Pending Repair',
                    'updated_at' => now(),
                ]);

            // 3. Log the reporting event
            DB::table('activity_logs')->insert([
                'user_id'         => $user->id ?? null,
                'action'          => 'REPORTED',
                'asset_name'      => $request->asset_name,
                'tracking_number' => $cleanTrackingNumber,
                'performed_by'    => $reporterName,
                'description'     => "Issue ({$request->issue_type}): {$description}",
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Damage report submitted successfully.');
    }
}