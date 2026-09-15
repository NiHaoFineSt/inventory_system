<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Asset Analysis</title>
    @vite(['resources/css/asset_analysis.css'])
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
                <a href="javascript:void(0);" class="nav-btn active" style="pointer-events: none; cursor: default;">
                    <span class="material-icons-outlined">category</span> Asset Analysis
                </a>
                <a href="{{ route('location_analysis') }}" class="nav-btn">
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
                <h1>Asset Type & Lifecycle Analysis</h1>
            </header>

            <!-- FILTERS BAR -->
            <form method="GET" action="{{ route('Asset_analysis') }}" id="filterForm">
                <section class="filter-bar">
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category" onchange="document.getElementById('filterForm').submit()">
                            <option value="All Categories">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Condition</label>
                        <select name="condition" onchange="document.getElementById('filterForm').submit()">
                            <option value="All Conditions">All Conditions</option>
                            @foreach($conditions as $cond)
                                <option value="{{ $cond }}" {{ request('condition') == $cond ? 'selected' : '' }}>{{ $cond }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department" onchange="document.getElementById('filterForm').submit()">
                            <option value="All Departments">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>
            </form>

            <!-- METRIC CARDS ROW -->
            <section class="metrics-grid">
                <div class="metric-card">
                    <h2>{{ $itAssetsCount }}</h2>
                    <p>IT Assets</p>
                </div>
                <div class="metric-card">
                    <h2>{{ $furnitureCount }}</h2>
                    <p>Furniture Units</p>
                </div>
                <div class="metric-card">
                    <h2>{{ $avgAssetAge }} Yrs</h2>
                    <p>Avg Asset Age</p>
                </div>
            </section>

            <!-- ANALYSIS CHARTS SECTION -->
            <section class="widgets-grid">

                <!-- ASSET CONDITION BREAKDOWN -->
                <div class="widget-card">
                    <h3>Asset Condition Breakdown</h3>
                    <div class="bar-item">
                        <span>Good Condition ({{ $conditionBreakdown['good']['percent'] }}%)</span>
                        <div class="bar-bg">
                            <div class="bar-fill" style="width: {{ $conditionBreakdown['good']['percent'] }}%; background-color: #65a30d;">
                                {{ $conditionBreakdown['good']['count'] }}
                            </div>
                        </div>
                    </div>
                    <div class="bar-item">
                        <span>Fair / Minor Wear ({{ $conditionBreakdown['fair']['percent'] }}%)</span>
                        <div class="bar-bg">
                            <div class="bar-fill" style="width: {{ $conditionBreakdown['fair']['percent'] }}%; background-color: #eab308;">
                                {{ $conditionBreakdown['fair']['count'] }}
                            </div>
                        </div>
                    </div>
                    <div class="bar-item">
                        <span>Critical / Needs Repair ({{ $conditionBreakdown['critical']['percent'] }}%)</span>
                        <div class="bar-bg">
                            <div class="bar-fill" style="width: {{ $conditionBreakdown['critical']['percent'] }}%; background-color: #ef4444;">
                                {{ $conditionBreakdown['critical']['count'] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALLOCATION BY DEPARTMENT -->
                <div class="widget-card">
                    <h3>Allocation by Department</h3>
                    <ul class="breakdown-list" style="list-style: none; padding: 0; margin: 0;">
                        @forelse($allocationByDept as $item)
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                                <span>{{ $item->dept }}</span>
                                <strong>{{ $item->asset_count }} Assets</strong>
                            </li>
                        @empty
                            <li><span>No allocations found</span></li>
                        @endforelse
                    </ul>
                </div>

            </section>

            <!-- LIFECYCLE & REPLACEMENT FORECAST MODULE -->
            <section class="table-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0;">Lifecycle & Replacement Forecast</h3>
                    <span style="font-size: 13px; color: #64748b;">Aggregated Category Metrics</span>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Units</th>
                            <th>Avg Life Expectancy</th>
                            <th>Age Brackets (0-2y / 3-5y / 5y+)</th>
                            <th>Due for Replacement</th>
                            <th>Health Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lifecycleForecast as $row)
                            @php
                                $lifeExpectancy = str_contains(strtolower($row->category), 'furniture') ? '8.0 Years' : '4.0 Years';
                                $health = $row->age_5_plus > 10 ? 'Needs Attention' : 'Optimal';
                                $healthBadgeClass = $row->age_5_plus > 10 ? 'in-use' : 'available';
                            @endphp
                            <tr>
                                <td><strong>{{ $row->category }}</strong></td>
                                <td>{{ $row->total_units }}</td>
                                <td>{{ $lifeExpectancy }}</td>
                                <td>
                                    <span style="color: #16a34a; font-weight: 600;">{{ $row->age_0_2 }}</span> / 
                                    <span style="color: #d97706; font-weight: 600;">{{ $row->age_3_5 }}</span> / 
                                    <span style="color: #dc2626; font-weight: 600;">{{ $row->age_5_plus }}</span>
                                </td>
                                <td><span class="badge {{ $row->age_5_plus > 0 ? 'damaged' : 'available' }}">{{ $row->age_5_plus }} Units</span></td>
                                <td><span class="badge {{ $healthBadgeClass }}">{{ $health }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center;">No assets found in the database.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

        </main>

    </div>

</body>
</html>