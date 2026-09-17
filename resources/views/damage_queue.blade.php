<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Damage Queue</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/damage_queue.css', 'resources/css/asset_directory.css', 'resources/css/tag_generator.css'])
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
                <a href="{{ route('Asset_analysis') }}" class="nav-btn {{ request()->routeIs('Asset_analysis') ? 'active' : '' }}">
                    <span class="material-icons-outlined">category</span> Asset Analysis
                </a>
                <a href="{{ route('location_analysis') }}" class="nav-btn {{ request()->routeIs('location_analysis') ? 'active' : '' }}">
                    <span class="material-icons-outlined">business</span> Location Analysis
                </a>
                <a href="{{ route('damage_queue') }}" class="nav-btn {{ request()->routeIs('damage_queue') ? 'active' : '' }}">
                    <span class="material-icons-outlined">report_problem</span> Damage Queue
                </a>
                <a href="{{ route('log_history') }}" class="nav-btn">
                    <span class="material-icons-outlined">history</span> Log History
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-link">
                        <span class="material-icons-outlined">logout</span> Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-banner">
                <h1>Damage Queue</h1>
            </header>

            <!-- ALERTS -->
            @if(session('success'))
                <div class="alert alert-success" style="padding: 12px 16px; background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 6px; margin-bottom: 16px;">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" style="padding: 12px 16px; background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; margin-bottom: 16px;">
                    {{ session('error') }}
                </div>
            @endif

            <!-- DAMAGE QUEUE TABLE -->
            <section class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Asset Name</th>
                            <th>Issue Type</th>
                            <th>Status</th>
                            <th>Reported Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($damagedAssets ?? $reports ?? [] as $item)
                            <tr>
                                <td><strong>{{ $item->tracking_number ?? 'N/A' }}</strong></td>
                                
                                <!-- ASSET CELL WITH READ-ONLY HOVER CARD -->
                                <td>
                                    <div class="asset-hover-wrapper">
                                        <span class="asset-hover-name">{{ $item->asset_name ?? 'N/A' }}</span>
                                        
                                        <div class="asset-hover-card">
                                            <div class="hover-title">Report Details</div>
                                            
                                            <div class="hover-field">
                                                <span class="hover-label">Reported By:</span>
                                                <span class="hover-value">{{ $item->reported_by ?? $item->reporter_name ?? 'Staff' }}</span>
                                            </div>
                                            
                                            <div class="hover-field">
                                                <span class="hover-label">Contact / Email:</span>
                                                <span class="hover-value">{{ $item->reporter_email ?? 'N/A' }}</span>
                                            </div>
                                            
                                            <div class="hover-field">
                                                <span class="hover-label">Issue Type:</span>
                                                <span class="hover-value">{{ $item->issue_type ?? 'N/A' }}</span>
                                            </div>
                                            
                                            <div class="hover-field">
                                                <span class="hover-label">Description:</span>
                                                <span class="hover-value">{{ $item->description ?? $item->issue_description ?? 'No details provided.' }}</span>
                                            </div>
                                            
                                            <div class="hover-field">
                                                <span class="hover-label">Reported Date:</span>
                                                <span class="hover-value">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td><span class="badge action-needed">{{ $item->issue_type ?? 'N/A' }}</span></td>
                                <td>{{ $item->status ?? 'Pending Inspection' }}</td>
                                <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d') : 'N/A' }}</td>
                                
                                <!-- ACTION CELL -->
                                <td class="text-center">
                                    @if(($item->status ?? '') !== 'Resolved')
                                        <form action="{{ route('damage_queue.resolve', $item->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-action btn-resolve" onclick="return confirm('Mark this damage report as resolved?')" style="background-color: #16a34a; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                                Resolve
                                            </button>
                                        </form>
                                    @else
                                        <span style="color: #16a34a; font-weight: 600;">✓ Completed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-row" style="text-align: center; padding: 20px; color: #64748b;">
                                    No reported damages in queue.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- REPORT DAMAGED ASSET MODAL -->
    <div id="reportDamageModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Report Damaged Asset</h3>
                <span class="material-icons-outlined close-btn" onclick="closeModal('reportDamageModal')">close</span>
            </div>

            <form id="damageReportForm" action="{{ route('report_damage.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="report-tracking-number">
                        TRACKING NUMBER (SCAN QR OR ENTER CODE)
                    </label>
                    <input type="text" id="report-tracking-number" name="tracking_number" class="form-control" placeholder="e.g. DSK-019" required>
                </div>

                <div class="form-group">
                    <label for="report-asset-name">
                        ASSET NAME
                    </label>
                    <input type="text" id="report-asset-name" name="asset_name" class="form-control readonly-input" placeholder="e.g. Standing Desk #7" readonly>
                </div>

                <div class="form-group">
                    <label for="issue_type">
                        ISSUE TYPE
                    </label>
                    <select id="issue_type" name="issue_type" class="form-control" required>
                        <option value="Hardware Malfunction">Hardware Malfunction</option>
                        <option value="Physical Damage">Physical Damage</option>
                        <option value="Missing Parts">Missing Parts</option>
                        <option value="Electrical Issue">Electrical Issue</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="damage_photo">
                        SNAP / UPLOAD DAMAGE PHOTO
                    </label>
                    <input type="file" id="damage_photo" name="damage_photo" class="form-control">
                </div>

                <div class="form-group">
                    <label for="issue_description">
                        ISSUE DESCRIPTION
                    </label>
                    <textarea id="issue_description" name="issue_description" rows="3" class="form-control" placeholder="Describe the damage or fault..."></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    Submit Damage Report
                </button>
            </form>
        </div>
    </div>

    <!-- AUTO-FETCH SCRIPT -->
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const trackingInput = document.getElementById('report-tracking-number');
            const nameInput = document.getElementById('report-asset-name');

            if (!trackingInput || !nameInput) return;

            trackingInput.addEventListener('input', debounce(fetchAssetName, 400));
            trackingInput.addEventListener('change', fetchAssetName);

            async function fetchAssetName() {
                const trackingNumber = trackingInput.value.trim();

                if (trackingNumber.length < 2) {
                    nameInput.value = '';
                    nameInput.placeholder = 'e.g. Standing Desk #7';
                    return;
                }

                try {
                    const baseRoute = "{{ route('assets.get_by_tracking', ['trackingNumber' => 'TARGET_TRACKING']) }}";
                    const requestUrl = baseRoute.replace('TARGET_TRACKING', encodeURIComponent(trackingNumber));

                    const response = await fetch(requestUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success' && data.asset) {
                        nameInput.value = data.asset.asset_name;
                    } else {
                        nameInput.value = '';
                        nameInput.placeholder = 'Asset code not found...';
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                }
            }

            function debounce(func, delay) {
                let timeout;
                return function (...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), delay);
                };
            }
        });
    </script>
</body>
</html>