<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DamageReport;

class DamageReportController extends Controller
{
    /**
     * Display the damage queue page with submitted reports.
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
     * Store new damage reports submitted by Staff or Asset Team.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required',
            'asset_name'      => 'required',
            'issue_type'      => 'required',
        ]);

        $user = Auth::user();

        $description = $request->input('description') 
                    ?? $request->input('issue_description') 
                    ?? '';

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('damage_photos', 'public');
        } elseif ($request->hasFile('damage_photo')) {
            $imagePath = $request->file('damage_photo')->store('damage_photos', 'public');
        }

        $reporterName = $user->name ?? $user->email ?? 'Aussy';

        DB::transaction(function () use ($request, $user, $reporterName, $description, $imagePath) {
            // 1. Insert into damage_reports table
            DB::table('damage_reports')->insert([
                'user_id'         => $user->id ?? 5,
                'reported_by'     => $reporterName,
                'tracking_number' => $request->tracking_number,
                'asset_name'      => $request->asset_name,
                'issue_type'      => $request->issue_type,
                'description'     => $description,
                'image'           => $imagePath,
                'status'          => 'Pending Inspection',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // 2. Insert into activity_logs table so it displays on Log History
            DB::table('activity_logs')->insert([
                'user_id'         => $user->id ?? 5,
                'action'          => 'REPORTED',
                'asset_name'      => $request->asset_name,
                'tracking_number' => $request->tracking_number,
                'performed_by'    => $reporterName,
                'description'     => "Issue ({$request->issue_type}): {$description}",
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Damage report submitted successfully.');
    }
}