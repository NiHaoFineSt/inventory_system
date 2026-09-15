<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Audit & Activity Log History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/log_history.css'])

</head>

<body>

    <div class="dashboard-container">

        <!-- SIDEBAR -->
        <aside class="sidebar no-print">
            <div class="sidebar-logo">
                <img src="{{ asset('logo2.png') }}" alt="Logo">
            </div>

            <nav class="sidebar-menu">
                <a href="{{ route('asset_dashboard') }}" class="nav-btn {{ request()->routeIs('asset_dashboard') ? 'active' : '' }}">
                    <span class="material-icons-outlined">dashboard</span> Overview
                </a>
                <a href="{{ route('asset_directory') }}" class="nav-btn {{ request()->routeIs('asset_directory') ? 'active' : '' }}">
                    <span class="material-icons-outlined">folder_open</span> Asset Directory
                </a>
                <a href="{{ route('asset_analysis') }}" class="nav-btn {{ request()->routeIs('Asset_analysis') ? 'active' : '' }}">
                    <span class="material-icons-outlined">category</span> Asset Analysis
                </a>
                <a href="{{ route('location_analysis') }}" class="nav-btn {{ request()->routeIs('location_analysis') ? 'active' : '' }}">
                    <span class="material-icons-outlined">business</span> Location Analysis
                </a>
                <a href="{{ route('damage_queue') }}" class="nav-btn {{ request()->routeIs('damage_queue') ? 'active' : '' }}">
                    <span class="material-icons-outlined">report_problem</span> Damage Queue
                </a>
                <a href="{{ route('log_history') }}" class="nav-btn {{ request()->routeIs('log_history') ? 'active' : '' }}" style="{{ request()->routeIs('log_history') ? 'pointer-events: none; cursor: default;' : '' }}">
                    <span class="material-icons-outlined">history</span> Log History
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="logout-link">
                        <span class="material-icons-outlined">logout</span> Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">

            <!-- HEADER BANNER -->
            <header class="top-banner" style="display: flex; justify-content: center; align-items: center; background-color: #031b4e; color: #fff; padding: 16px; border-radius: 8px;">
                <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff;">Audit & Activity Log History</h1>
            </header>

            <!-- FILTERS BAR -->
            <section class="filter-card" style="margin-top: 20px;">
                <div class="filter-group">
                    <label for="actionFilter">ACTION TYPE</label>
                    <select id="actionFilter" class="filter-control" onchange="filterLogs()">
                        <option value="all">All Actions</option>
                        <option value="ADDED">Added</option>
                        <option value="EDITED">Edited</option>
                        <option value="DELETED">Deleted</option>
                        <option value="REPORTED">Reported</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="searchKeyword">SEARCH KEYWORD</label>
                    <input type="text" id="searchKeyword" class="filter-control" placeholder="Search tracking #, asset name..." onkeyup="filterLogs()">
                </div>
            </section>

            <!-- LOG TABLE CARD -->
            <section class="table-card">
                <table class="data-table" id="logTable">
                    <thead>
                        <tr>
                            <th>DATE & TIME</th>
                            <th>ACTION</th>
                            <th>ASSET TARGET</th>
                            <th>TRACKING / QR CODE</th>
                            <th>PERFORMED BY</th>
                            <th>DETAILS / MODIFICATIONS</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
    @forelse($logs as $log)
        @php
            $action = strtoupper($log->action ?? 'ADDED');
            $badgeClass = match(strtolower($action)) {
                'added', 'add', 'created' => 'badge-added',
                'edited', 'updated' => 'badge-edited',
                'deleted' => 'badge-deleted',
                'reported' => 'badge-reported',
                default => 'badge-default',
            };
        @endphp
        <tr data-action="{{ $action }}">
            <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') : 'N/A' }}</td>
            <td>
                <span class="badge-action {{ $badgeClass }}">{{ $action }}</span>
            </td>
            <td><strong>{{ $log->asset_name ?? '-' }}</strong></td>
            <td>
                @if(($log->tracking_number ?? '-') !== '-')
                    <code>{{ $log->tracking_number }}</code>
                @else
                    -
                @endif
            </td>
            <td>{{ $log->performed_by ?? 'System' }}</td>
            <td class="log-details">{{ $log->description ?? '-' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 36px; font-size: 14px;">
                No activity logs recorded yet.
            </td>
        </tr>
    @endforelse
</tbody>
                </table>
            </section>

        </main>

    </div>

    <script>
        function filterLogs() {
            const action = document.getElementById('actionFilter').value;
            const keyword = document.getElementById('searchKeyword').value.toLowerCase();
            const rows = document.querySelectorAll('#logTableBody tr');

            rows.forEach(row => {
                const rowAction = row.getAttribute('data-action');
                const rowText = row.innerText.toLowerCase();

                const actionMatch = (action === 'all' || rowAction === action);
                const keywordMatch = (keyword === '' || rowText.includes(keyword));

                if (actionMatch && keywordMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>

</body>

</html>