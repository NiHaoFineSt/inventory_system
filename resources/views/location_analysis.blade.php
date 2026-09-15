<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Location Analysis</title>
    @vite(['resources/css/location_analysis.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>

<body>

    <div class="dashboard-container">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="{{ asset('logo2.png') }}" alt="Logo">
            </div>

            <nav class="sidebar-menu">
                <a href="{{ route('asset_dashboard') }}" class="nav-btn">
                    <span class="material-icons-outlined">dashboard</span> Overview
                </a>
                <a href="{{ route('asset_directory') }}" class="nav-btn">
                    <span class="material-icons-outlined">folder_open</span> Asset Directory
                </a>
                <a href="{{ route('asset_analysis') }}" class="nav-btn">
                    <span class="material-icons-outlined">category</span> Asset Analysis
                </a>
                <a href="javascript:void(0);" class="nav-btn active" style="pointer-events: none; cursor: default;">
                    <span class="material-icons-outlined">business</span> Location Analysis
                </a>
                <a href="{{ route('damage_queue') }}" class="nav-btn">
                    <span class="material-icons-outlined">report_problem</span> Damage Queue
                </a>
                <a href="{{ route('log_history') }}" class="nav-btn">
                    <span class="material-icons-outlined">history</span> Log History
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="logout-link" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; color: inherit; font: inherit; padding: 0;">
                        <span class="material-icons-outlined">logout</span> Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">

            <!-- TOP HEADER BANNER -->
            <header class="top-banner">
                <h1>Location & Office Distribution Analysis</h1>
            </header>

            <!-- FILTERS BAR -->
            <form method="GET" action="{{ route('location_analysis') }}" id="locationFilterForm">
                <section class="filter-bar">
                    <div class="filter-group">
                        <label>Select Location</label>
                        <select name="location" id="filter-location" onchange="document.getElementById('locationFilterForm').submit()">
                            <option value="All">All Locations</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc }}" {{ request('location') == $loc ? 'selected' : '' }}>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status Filter</label>
                        <select name="status" id="filter-status" onchange="document.getElementById('locationFilterForm').submit()">
                            <option value="All">All Statuses</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>
            </form>

            <!-- METRIC CARDS ROW -->
            <section class="metrics-grid">
                <div class="metric-card">
                    <h2>{{ $totalActiveSites }}</h2>
                    <p>Active Sites</p>
                </div>
                <div class="metric-card">
                    <h2>{{ $totalAssetsCount }}</h2>
                    <p>Total Assets</p>
                </div>
                <div class="metric-card">
                    <h2>{{ $unassignedCount }}</h2>
                    <p>Unassigned Assets</p>
                </div>
            </section>

            <!-- LOCATION BREAKDOWN CARDS -->
            <section class="widgets-grid" style="grid-template-columns: 1fr;">

                <!-- ASSET COUNT BY SITE -->
                <div class="widget-card">
                    <h3>Asset Distribution by Site</h3>
                    @forelse($distributionBySite as $site)
                        <div class="bar-item">
                            <span>{{ $site->department_name }} ({{ $site->percentage }}%)</span>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width: {{ $site->percentage }}%; background-color: #65a30d;">
                                    {{ $site->count }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color: #64748b;">No location distribution data found.</p>
                    @endforelse
                </div>

            </section>

            <!-- LOCATION ASSET TABLE -->
            <section class="table-card">
                <h3>Location Asset Directory</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="location-table-body">
                        @forelse($assets as $asset)
                            @php
                                $statusClass = strtolower($asset->display_status ?? '') === 'active' ? 'available' : 'damaged';
                            @endphp
                            <tr>
                                <td><strong>{{ $asset->tracking_number ?? $asset->asset_tag ?? 'N/A' }}</strong></td>
                                <td>{{ $asset->asset_name ?? $asset->name ?? $asset->item_name ?? 'N/A' }}</td>
                                <td>{{ $asset->category ?? 'N/A' }}</td>
                                <td>{{ $asset->display_location ?? 'Storage' }}</td>
                                <td>{{ $asset->display_assigned_to ?? 'Unassigned' }}</td>
                                <td>
                                    <span class="badge {{ $statusClass }}">
                                        {{ $asset->display_status ?? 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center;">No assets match the selected location/status filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

        </main>

    </div>

</body>

</html>