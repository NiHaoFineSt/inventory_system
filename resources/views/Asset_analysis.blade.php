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
                    <h2 id="metricItAssets">{{ $itAssetsCount }}</h2>
                    <p>IT Assets</p>
                </div>
                <div class="metric-card">
                    <h2 id="metricFurniture">{{ $furnitureCount }}</h2>
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
                        <span id="labelGood">Good Condition ({{ $conditionBreakdown['good']['percent'] }}%)</span>
                        <div class="bar-bg">
                            <div class="bar-fill" id="barGood" style="width: {{ $conditionBreakdown['good']['percent'] }}%; background-color: #65a30d;">
                                {{ $conditionBreakdown['good']['count'] }}
                            </div>
                        </div>
                    </div>
                    <div class="bar-item">
                        <span id="labelFair">Fair / Minor Wear ({{ $conditionBreakdown['fair']['percent'] }}%)</span>
                        <div class="bar-bg">
                            <div class="bar-fill" id="barFair" style="width: {{ $conditionBreakdown['fair']['percent'] }}%; background-color: #eab308;">
                                {{ $conditionBreakdown['fair']['count'] }}
                            </div>
                        </div>
                    </div>
                    <div class="bar-item">
                        <span id="labelCritical">Critical / Needs Repair ({{ $conditionBreakdown['critical']['percent'] }}%)</span>
                        <div class="bar-bg">
                            <div class="bar-fill" id="barCritical" style="width: {{ $conditionBreakdown['critical']['percent'] }}%; background-color: #ef4444;">
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

    <script>
        const rawAnalysisData = @json($assets ?? []);

        // 1. Deduplicate raw items using exact Dashboard Base Code logic
        const deduplicatedAnalysisMap = new Map();

        rawAnalysisData.forEach(item => {
            const rawTracking = item.tracking_number || `AST-${item.id}`;
            const baseCode = rawTracking.split('|')[0].trim().replace(/^TRK-/i, '').toUpperCase();

            if (!baseCode) return;

            if (!deduplicatedAnalysisMap.has(baseCode)) {
                deduplicatedAnalysisMap.set(baseCode, item);
            } else if (deduplicatedAnalysisMap.get(baseCode).tracking_number?.startsWith('TRK-') && !rawTracking.startsWith('TRK-')) {
                deduplicatedAnalysisMap.set(baseCode, item);
            }
        });

        const analysisDataset = Array.from(deduplicatedAnalysisMap.values());

        // 2. Shared repair check
        function isDamaged(item) {
            const damageStatus = String(item.latest_damage_status ?? item.damage_status ?? item.status ?? '').toLowerCase().trim();
            const damageIssue = String(item.latest_damage_issue ?? item.issue_type ?? item.description ?? '').toLowerCase().trim();
            const assetStatus = String(item.asset_status ?? item.status ?? '').toLowerCase().trim();
            const assetCondition = String(item.asset_condition ?? item.condition ?? '').toLowerCase().trim();

            if (damageStatus === 'resolved' || damageStatus === 'completed') {
                return false;
            }

            const keywords = ['pending', 'inspection', 'missing', 'damage', 'damaged', 'repair', 'broken', 'faulty', 'malfunction', 'reported', 'critical', 'needs repair'];

            return keywords.some(kw => 
                damageStatus.includes(kw) || 
                damageIssue.includes(kw) || 
                assetStatus.includes(kw) || 
                assetCondition.includes(kw)
            );
        }

        // 3. Sync UI elements on load
        document.addEventListener('DOMContentLoaded', () => {
            if (!analysisDataset.length) return;

            const total = analysisDataset.length;
            const criticalCount = analysisDataset.filter(isDamaged).length;
            const goodCount = analysisDataset.filter(item => !isDamaged(item) && String(item.condition || item.asset_condition || '').toLowerCase().includes('good')).length;
            const fairCount = Math.max(0, total - criticalCount - goodCount);

            const criticalPct = total > 0 ? Math.round((criticalCount / total) * 100) : 0;
            const goodPct = total > 0 ? Math.round((goodCount / total) * 100) : 0;
            const fairPct = total > 0 ? Math.round((fairCount / total) * 100) : 0;

            // Recalculate IT vs Furniture Metric Cards
            const itCount = analysisDataset.filter(i => {
                const cat = String(i.category || i.type || '').toLowerCase();
                return cat.includes('it') || cat.includes('hardware') || cat.includes('monitor') || cat.includes('laptop');
            }).length;

            const furnitureCount = analysisDataset.filter(i => {
                const cat = String(i.category || i.type || '').toLowerCase();
                return cat.includes('furniture') || cat.includes('chair') || cat.includes('desk');
            }).length;

            // Update DOM metrics
            const elIt = document.getElementById('metricItAssets');
            const elFurn = document.getElementById('metricFurniture');
            if (elIt) elIt.innerText = itCount;
            if (elFurn) elFurn.innerText = furnitureCount;

            // Update Progress Bar visuals
            const barGood = document.getElementById('barGood');
            const labelGood = document.getElementById('labelGood');
            if (barGood && labelGood) {
                barGood.style.width = goodPct + '%';
                barGood.innerText = goodCount;
                labelGood.innerText = `Good Condition (${goodPct}%)`;
            }

            const barFair = document.getElementById('barFair');
            const labelFair = document.getElementById('labelFair');
            if (barFair && labelFair) {
                barFair.style.width = fairPct + '%';
                barFair.innerText = fairCount;
                labelFair.innerText = `Fair / Minor Wear (${fairPct}%)`;
            }

            const barCritical = document.getElementById('barCritical');
            const labelCritical = document.getElementById('labelCritical');
            if (barCritical && labelCritical) {
                barCritical.style.width = criticalPct + '%';
                barCritical.innerText = criticalCount;
                labelCritical.innerText = `Critical / Needs Repair (${criticalPct}%)`;
            }
        });
    </script>

</body>
</html>