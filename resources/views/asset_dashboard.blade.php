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
                <a href="{{ route('asset_directory') }}" class="nav-btn {{ request()->routeIs('asset_directory') ? 'active' : '' }}" style="{{ request()->routeIs('asset_directory') ? 'pointer-events: none; cursor: default;' : '' }}">
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
                    <button type="submit" class="logout-link" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; color: inherit; font: inherit; padding: 0;">
                        <span class="material-icons-outlined">logout</span> Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="main-content">

            <!-- TOP HEADER BANNER -->
            <header class="top-banner">
                <h1>IT Asset Management Dashboard</h1>
            </header>

            <!-- FILTERS BAR -->
            <section class="filter-bar">
                <div class="filter-group">
                    <label for="filterYear">Year</label>
                    <select id="filterYear" onchange="applyFilters()">
                        <option value="All">All</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterMonth">Month</label>
                    <select id="filterMonth" onchange="applyFilters()">
                        <option value="All">All</option>
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterType">Asset Type</label>
                    <select id="filterType" onchange="applyFilters()">
                        <option value="All">All</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Chair">Chair</option>
                        <option value="Desk">Desk</option>
                        <option value="Laptop">Laptop</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterAssigned">Assigned To</label>
                    <select id="filterAssigned" onchange="applyFilters()">
                        <option value="All">All</option>
                        <option value="Head Office">Head Office</option>
                        <option value="Branch A">Branch A</option>
                        <option value="Branch B">Branch B</option>
                        <option value="Remote">Remote</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus" onchange="applyFilters()">
                        <option value="All">All</option>
                        <option value="Available">Available</option>
                        <option value="In Use">In Use</option>
                        <option value="Damaged">Damaged</option>
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

            <!-- ASSET SUMMARY TABLE (BELOW CHARTS) -->
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
    // Fetch dynamic dataset from controller
    const rawData = @json($assets);

    // Map your database column names to the dashboard structure
    const assetDataset = rawData.map(item => {
        const dateObj = item.created_at ? new Date(item.created_at) : new Date();
        const yearStr = dateObj.getFullYear().toString();
        const monthStr = String(dateObj.getMonth() + 1).padStart(2, '0');

        return {
            id: item.tracking_number || `AST-${item.id}`,
            type: item.category || 'Other',              // Maps 'category' column
            location: item.location || 'Unassigned',     // Maps 'location' column
            status: item.condition || 'Available',        // Maps 'condition' column
            year: yearStr,
            month: monthStr
        };
    });

    let gaugeChartInstance, locationChartInstance, typeChartInstance;

    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
        applyFilters();
    });

    function initCharts() {
        // Semi-circle doughnut gauge
        const ctxGauge = document.getElementById('gaugeChart').getContext('2d');
        gaugeChartInstance = new Chart(ctxGauge, {
            type: 'doughnut',
            data: {
                labels: ['Operational', 'Issues/Damaged'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#22c55e', '#ef4444']
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

        // Location doughnut chart
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

        // Type bar chart
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

    function applyFilters() {
        const year = document.getElementById('filterYear').value;
        const month = document.getElementById('filterMonth').value;
        const type = document.getElementById('filterType').value;
        const assigned = document.getElementById('filterAssigned').value;
        const status = document.getElementById('filterStatus').value;

        const filteredData = assetDataset.filter(item => {
            return (year === 'All' || item.year === year) &&
                   (month === 'All' || item.month === month) &&
                   (type === 'All' || item.type === type) &&
                   (assigned === 'All' || item.location === assigned) &&
                   (status === 'All' || item.status === status);
        });

        renderTable(filteredData);
        updateDashboardMetrics(filteredData);
        updateCharts(filteredData);
    }

    function renderTable(data) {
        const tbody = document.getElementById('assetSummaryBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="padding: 12px; text-align: center;">No assets found in database matching filters.</td></tr>`;
            return;
        }

        data.forEach(item => {
            const row = `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px;">${item.id}</td>
                    <td style="padding: 12px;">${item.type}</td>
                    <td style="padding: 12px;">${item.location}</td>
                    <td style="padding: 12px;">${item.status}</td>
                    <td style="padding: 12px;">${item.year}</td>
                    <td style="padding: 12px;">${item.month}</td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    function updateDashboardMetrics(data) {
        const total = data.length;
        const damaged = data.filter(d => d.status.toLowerCase().includes('damaged') || d.status.toLowerCase().includes('repair')).length;
        const active = total - damaged;

        document.getElementById('totalAssets').innerText = total;
        document.getElementById('activeAssets').innerText = active;
        document.getElementById('pendingRepairs').innerText = damaged;
        document.getElementById('operationalRate').innerText = total > 0 ? (((total - damaged) / total) * 100).toFixed(1) + '%' : '0%';
    }

    function updateCharts(data) {
        const total = data.length;
        const damaged = data.filter(d => d.status.toLowerCase().includes('damaged') || d.status.toLowerCase().includes('repair')).length;
        const active = total - damaged;

        // Gauge Update
        gaugeChartInstance.data.datasets[0].data = [active, damaged];
        gaugeChartInstance.update();

        // Dynamic Location Breakdown
        const locationCounts = {};
        data.forEach(d => {
            locationCounts[d.location] = (locationCounts[d.location] || 0) + 1;
        });
        locationChartInstance.data.labels = Object.keys(locationCounts);
        locationChartInstance.data.datasets[0].data = Object.values(locationCounts);
        locationChartInstance.update();

        // Dynamic Asset Type Breakdown
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

</body>

</html>