<?php

namespace App\Http\Controllers;

use App\Services\Analytics\LocationAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationAnalysisController extends Controller
{
    public function index(Request $request, LocationAnalyticsService $service): View
    {
        return view('location_analysis', $service->build($request));
    }
}
