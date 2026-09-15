<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DamageReport;
use App\Models\AuditLog;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $reports = DamageReport::where('user_id', $user->id)
            ->latest()
            ->get();

        return view('staff_dashboard', compact('user', 'reports'));
    }

    public function storeReport(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string',
            'asset_name'      => 'required|string',
            'issue_type'      => 'required|string',
            'description'     => 'required|string',
        ]);

        DamageReport::create([
            'user_id'         => Auth::id(),
            'tracking_number' => $request->tracking_number,
            'asset_name'      => $request->asset_name,
            'issue_type'      => $request->issue_type,
            'description'     => $request->description,
            'status'          => 'Pending',
        ]);

        Asset::where('tracking_number', $request->tracking_number)->update(['condition' => 'Damaged']);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'ADDED',
            'asset_name'  => $request->asset_name,
            'description' => "Reported issue for {$request->tracking_number}: {$request->issue_type} - {$request->description}",
        ]);

        return back()->with('success', 'Damage report submitted successfully!');
    }
}