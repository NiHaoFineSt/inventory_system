<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AssetAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetAnalysisController extends Controller
{
    public function index(Request $request, AssetAnalyticsService $service): View
    {
        return view('asset_analysis', $service->build($request));
    }
}
