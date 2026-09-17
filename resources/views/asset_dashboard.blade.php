<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Dashboard</title>
    @vite(['resources/css/asset_dashboard.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="dashboard-container">

        <!-- SIDEBAR WITH LOGO AT THE TOP -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="{{ asset('logo2.png') }}" alt="Logo">
            </div>

            <nav class="sidebar-menu">
                <a href="javascript:void(0);" class="nav-btn active" style="pointer-events: none; cursor: default;">
                    <span class="material-icons-outlined">dashboard</span> Overview
                </a>
                <a href="{{ route('asset_directory') }}" class="nav-btn {{ request()->routeIs('asset_directory') ? 'active' : '' }}">
                    <span class="material-icons-outlined">folder_open</span> Asset Directory
                </a>
                <a href="{{ route('Asset_analysis') }}" class="nav-btn">
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
                    <button type="submit" class="logout-link" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; color: #ffffff; font: inherit; padding: 0;">
                        <span class="material-icons-outlined" style="color: #ffffff;">logout</span> Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="main-content">

            <!-- TOP HEADER BANNER -->
            <header class="top-banner">
                <h1>Asset Management Dashboard</h1>
            </header>

            <!-- FILTERS BAR -->
            <section class="filter-bar">
                <div class="filter-group">
                    <label for="filterYear">Year</label>
                    <select id="filterYear" onchange="applyFilters()">
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterMonth">Month</label>
                    <select id="filterMonth" onchange="applyFilters()">
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterType">Asset Type</label>
                    <select id="filterType" onchange="applyFilters()">
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterAssigned">Assigned To</label>
                    <select id="filterAssigned" onchange="applyFilters()">
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterStatus">Status / Condition</label>
                    <select id="filterStatus" onchange="applyFilters()">
                        <option value="All">All</option>
                        <option value="Active">Active / Assigned</option>
                        <option value="Unassigned">Unassigned / Inactive</option>
                        <option value="Damaged">Damaged / Pending Repair</option>
                    </select>
                </div>
            </section>

            <!-- METRIC CARDS TOP ROW -->
            <section class="metrics-grid">
                <div class="metric-card">
                    <h2 id="totalAssets">0</h2>
                    <p>Total Assets</p>
                </div>
                <div class="metric-card">
                    <h2 id="activeAssets">0</h2>
                    <p>Active Assets</p>
                </div>
                <div class="metric-card">
                    <h2 id="pendingRepairs">0</h2>
                    <p>Pending Repairs</p>
                </div>
                <div class="metric-card">
                    <h2 id="operationalRate">0%</h2>
                    <p>Operational Rate</p>
                </div>
            </section>

            <!-- WIDGETS LOWER GRID -->
            <section class="widgets-grid">

                <!-- GAUGE CARD -->
                <div class="widget-card gauge-widget">
                    <h3>Active Assets %</h3>
                    <div style="position: relative; height: 180px; width: 100%;">
                        <canvas id="gaugeChart"></canvas>
                    </div>
                </div>

                <!-- LOCATION BREAKDOWN CARD -->
                <div class="widget-card">
                    <h3>Assets by Location</h3>
                    <div style="position: relative; height: 180px; width: 100%;">
                        <canvas id="locationChart"></canvas>
                    </div>
                </div>

                <!-- TYPE BAR CHART -->
                <div class="widget-card">
                    <h3>Assets by Type</h3>
                    <div style="position: relative; height: 180px; width: 100%;">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>

            </section>

            <!-- ASSET SUMMARY TABLE -->
            <section class="widget-card" style="margin-top: 24px;">
                <h3>Asset Summary</h3>
                <div style="overflow-x: auto;">
                    <table class="asset-summary-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px;">Tracking Number</th>
                                <th style="padding: 12px;">Asset Type</th>
                                <th style="padding: 12px;">Assigned To</th>
                                <th style="padding: 12px;">Status</th>
                                <th style="padding: 12px;">Condition</th>
                                <th style="padding: 12px;">Year</th>
                                <th style="padding: 12px;">Month</th>
                            </tr>
                        </thead>
                        <tbody id="assetSummaryBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </section>

        </main>

    </div>

   <script>
    const rawData = @json($assets ?? []);

    // 1. Deduplicate raw items using a Base Code map
    const deduplicatedMap = new Map();

    rawData.forEach(item => {
        const rawTracking = item.tracking_number || `AST-${item.id}`;
        const baseCode = rawTracking.split('|')[0].trim().replace(/^TRK-/i, '').toUpperCase();

        if (!baseCode) return;

        if (!deduplicatedMap.has(baseCode)) {
            deduplicatedMap.set(baseCode, item);
        } else if (deduplicatedMap.get(baseCode).tracking_number?.startsWith('TRK-') && !rawTracking.startsWith('TRK-')) {
            deduplicatedMap.set(baseCode, item);
        }
    });

    // 2. Map deduplicated data into structured dashboard records
    const assetDataset = Array.from(deduplicatedMap.values()).map(item => {
        const dateObj = item.created_at ? new Date(item.created_at) : new Date();
        const yearStr = dateObj.getFullYear().toString();
        const monthStr = String(dateObj.getMonth() + 1).padStart(2, '0');

        let cleanLocation = item.location || item.assigned_to || 'Unassigned';
        cleanLocation = cleanLocation.replace(/-\s*aussy/gi, '').trim();

        const statusVal = item.latest_damage_status ?? item.damage_status ?? item.status ?? '';
        const issueVal = item.latest_damage_issue ?? item.issue_type ?? item.description ?? '';
        const mainStatusVal = item.asset_status ?? item.status ?? '';
        const mainCondVal = item.asset_condition ?? item.condition ?? '';

        const trackingCode = item.tracking_number || `AST-${item.id}`;
        const cleanTrackingCode = trackingCode.split('|')[0].trim().replace(/^TRK-/i, '').toUpperCase();

        return {
            id: cleanTrackingCode,
            type: item.category || item.type || item.asset_type || 'Other',
            location: cleanLocation,
            status: mainStatusVal || 'Inactive',
            condition: mainCondVal || 'Good',
            damageStatus: String(statusVal).toLowerCase().trim(),
            damageIssue: String(issueVal).toLowerCase().trim(),
            assetStatus: String(mainStatusVal).toLowerCase().trim(),
            assetCondition: String(mainCondVal).toLowerCase().trim(),
            year: yearStr,
            month: monthStr
        };
    });

    let gaugeChartInstance, locationChartInstance, typeChartInstance;

    document.addEventListener('DOMContentLoaded', () => {
        populateSelectFilters();
        initCharts();
        applyFilters();
    });

    function populateSelectFilters() {
        const populate = (elementId, values) => {
            const select = document.getElementById(elementId);
            if (!select) return;
            const currentVal = select.value;
            select.innerHTML = '<option value="All">All</option>';
            Array.from(values).sort().forEach(val => {
                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = val;
                select.appendChild(opt);
            });
            if (currentVal) select.value = currentVal;
        };

        const years = new Set(assetDataset.map(d => d.year));
        const months = new Set(assetDataset.map(d => d.month));
        const types = new Set(assetDataset.map(d => d.type));
        const locations = new Set(assetDataset.map(d => d.location));

        populate('filterYear', years);
        populate('filterMonth', months);
        populate('filterType', types);
        populate('filterAssigned', locations);
    }

    function initCharts() {
        const ctxGauge = document.getElementById('gaugeChart').getContext('2d');
        gaugeChartInstance = new Chart(ctxGauge, {
            type: 'doughnut',
            data: {
                labels: ['Active / Assigned', 'Unassigned / Inactive', 'Damaged / Pending Repair'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                    borderWidth: 1
                }]
            },
            options: {
                rotation: -90,
                circumference: 180,
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        const ctxLoc = document.getElementById('locationChart').getContext('2d');
        locationChartInstance = new Chart(ctxLoc, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#c084fc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        const ctxType = document.getElementById('typeChart').getContext('2d');
        typeChartInstance = new Chart(ctxType, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Assets',
                    data: [],
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    function isDamaged(item) {
        if (item.damageStatus === 'resolved' || item.damageStatus === 'completed') {
            return false;
        }

        const keywords = ['pending', 'inspection', 'missing', 'damage', 'damaged', 'repair', 'broken', 'faulty', 'malfunction', 'reported', 'critical', 'needs repair'];

        const inDamageTable = keywords.some(kw => item.damageStatus.includes(kw) || item.damageIssue.includes(kw));
        const inAssetTable = keywords.some(kw => item.assetStatus.includes(kw) || item.assetCondition.includes(kw));

        return inDamageTable || inAssetTable;
    }

    function isActiveAssigned(item) {
        const loc = item.location.toLowerCase();
        
        if (item.assetStatus === 'unassigned' || item.assetStatus === 'inactive') {
            return false;
        }

        const isAssigned = loc !== '' && loc !== 'unassigned' && loc !== 'none';
        const isExplicitActive = ['active', 'in use', 'assigned', 'operational'].includes(item.assetStatus);
        
        return (isAssigned || isExplicitActive) && !isDamaged(item);
    }

    function applyFilters() {
        const year = document.getElementById('filterYear').value;
        const month = document.getElementById('filterMonth').value;
        const type = document.getElementById('filterType').value;
        const assigned = document.getElementById('filterAssigned').value;
        const status = document.getElementById('filterStatus').value;

        const filteredData = assetDataset.filter(item => {
            const matchYear = (year === 'All' || item.year === year);
            const matchMonth = (month === 'All' || item.month === month);
            const matchType = (type === 'All' || item.type.toLowerCase() === type.toLowerCase());
            const matchAssigned = (assigned === 'All' || item.location.toLowerCase() === assigned.toLowerCase());
            
            let matchStatus = (status === 'All');
            if (status === 'Active') matchStatus = isActiveAssigned(item);
            if (status === 'Unassigned') matchStatus = !isActiveAssigned(item) && !isDamaged(item);
            if (status === 'Damaged') matchStatus = isDamaged(item);

            return matchYear && matchMonth && matchType && matchAssigned && matchStatus;
        });

        renderTable(filteredData);
        updateDashboardMetrics(filteredData);
        updateCharts(filteredData);
    }

    function renderTable(data) {
        const tbody = document.getElementById('assetSummaryBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="padding: 12px; text-align: center;">No assets found matching filters.</td></tr>`;
            return;
        }

        data.forEach(item => {
            const row = `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px;"><strong>${item.id}</strong></td>
                    <td style="padding: 12px;">${item.type}</td>
                    <td style="padding: 12px;">${item.location}</td>
                    <td style="padding: 12px;">${item.status}</td>
                    <td style="padding: 12px;">${item.condition}</td>
                    <td style="padding: 12px;">${item.year}</td>
                    <td style="padding: 12px;">${item.month}</td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    function updateDashboardMetrics(data) {
        const total = data.length;
        const active = data.filter(d => isActiveAssigned(d)).length;
        const damaged = data.filter(d => isDamaged(d)).length;
        const operational = total - damaged;

        document.getElementById('totalAssets').innerText = total;
        document.getElementById('activeAssets').innerText = active;
        document.getElementById('pendingRepairs').innerText = damaged;
        document.getElementById('operationalRate').innerText = total > 0 ? ((operational / total) * 100).toFixed(1) + '%' : '0%';
    }

    function updateCharts(data) {
        const active = data.filter(d => isActiveAssigned(d)).length;
        const damaged = data.filter(d => isDamaged(d)).length;
        const inactive = Math.max(0, data.length - active - damaged);

        gaugeChartInstance.data.datasets[0].data = [active, inactive, damaged];
        gaugeChartInstance.update();

        const locationCounts = {};
        data.forEach(d => {
            const loc = d.location || 'Unassigned';
            locationCounts[loc] = (locationCounts[loc] || 0) + 1;
        });
        locationChartInstance.data.labels = Object.keys(locationCounts);
        locationChartInstance.data.datasets[0].data = Object.values(locationCounts);
        locationChartInstance.update();

        const typeCounts = {};
        data.forEach(d => {
            typeCounts[d.type] = (typeCounts[d.type] || 0) + 1;
        });
        typeChartInstance.data.labels = Object.keys(typeCounts);
        typeChartInstance.data.datasets[0].data = Object.values(typeCounts);
        typeChartInstance.update();
    }
</script>
</body>

</html>