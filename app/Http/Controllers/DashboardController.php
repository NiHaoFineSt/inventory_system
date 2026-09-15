<?php

namespace App\Http\Controllers;

use App\Models\Asset; // Ensure your Asset model points to the 'asset' table
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch all assets from the 'asset' table
        $assets = Asset::all();

        // Pass the dataset to your Blade view
        return view('asset_dashboard', compact('assets'));
    }
}