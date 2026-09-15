<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\DamageReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function assetDashboard(): View
    {
        return view('asset_dashboard', ['assets' => Asset::all()]);
    }

    public function staffDashboard(): View
    {
        $user = Auth::user();

        $reports = DamageReport::where('user_id', $user->id)->latest()->get();
        $assignedAssets = Asset::where('assigned_to', $user->name)->get();

        return view('staff_dashboard', compact('user', 'reports', 'assignedAssets'));
    }
}
