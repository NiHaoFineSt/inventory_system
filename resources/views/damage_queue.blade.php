<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Damage Queue</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/asset_directory.css', 'resources/css/tag_generator.css'])

    <style>
        .asset-hover-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .asset-hover-name {
            color: #2563eb;
            font-weight: 600;
            text-decoration: underline;
        }

        .asset-hover-card {
            display: none;
            position: absolute;
            left: 0;
            top: 100%;
            z-index: 100;
            width: 280px;
            padding: 12px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            text-align: left;
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
        }

        .asset-hover-wrapper:hover .asset-hover-card {
            display: block;
        }

        .hover-title {
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .hover-field {
            margin-bottom: 4px;
        }

        .hover-label {
            font-weight: 600;
            color: #64748b;
        }
    </style>
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
                <a href="{{ route('damage_queue') }}" class="nav-btn {{ request()->routeIs('damage_queue') ? 'active' : '' }}" style="{{ request()->routeIs('damage_queue') ? 'pointer-events: none; cursor: default;' : '' }}">
                    <span class="material-icons-outlined">report_problem</span> Damage Queue
                </a>
                <a href="{{ route('log_history') }}" class="nav-btn">
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

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-banner" style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Damage Queue</h1>
            </header>

            <!-- DAMAGE QUEUE TABLE -->
            <section class="table-card" style="margin-top: 20px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Asset Name</th>
                            <th>Issue Type</th>
                            <th>Status</th>
                            <th>Reported Date</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($damagedAssets ?? $reports ?? [] as $item)
                            <tr>
                                <td><strong>{{ $item->tracking_number ?? 'N/A' }}</strong></td>
                                
                                <td>
                                    <div class="asset-hover-wrapper">
                                        <span class="asset-hover-name">{{ $item->asset_name ?? 'N/A' }}</span>
                                        <div class="asset-hover-card">
                                            <div class="hover-title">Report Details</div>
                                            <div class="hover-field"><span class="hover-label">Reported By:</span> {{ $item->reported_by ?? 'Staff' }}</div>
                                            <div class="hover-field"><span class="hover-label">Issue Type:</span> {{ $item->issue_type ?? 'N/A' }}</div>
                                            <div class="hover-field"><span class="hover-label">Description:</span> {{ $item->description ?? 'No description provided.' }}</div>
                                            <div class="hover-field"><span class="hover-label">Date:</span> {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td><span class="badge action-needed">{{ $item->issue_type ?? 'N/A' }}</span></td>
                                <td>{{ $item->status?->value ?? 'Pending' }}</td>
                                <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d') : 'N/A' }}</td>

                                <td style="text-align: center;">
                                    @if ($item->status?->value !== 'Resolved')
                                        <button type="button" class="btn-action resolve-btn" data-report-id="{{ $item->id }}" style="background-color: #2563eb; color: #fff; padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer;">Resolve</button>
                                    @else
                                        <span style="color: #16a34a; font-weight: 600;">Resolved</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
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
    <div id="reportDamageModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div class="modal-card" style="background: #fff; width: 100%; max-width: 480px; padding: 24px; border-radius: 8px; position: relative;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0;">Report Damaged Asset</h3>
                <span class="material-icons-outlined close-btn" style="cursor: pointer; color: #64748b;" onclick="closeModal('reportDamageModal')">close</span>
            </div>

            <form id="damageReportForm" action="{{ route('report_damage.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="report-tracking-number" style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                        TRACKING NUMBER (SCAN QR OR ENTER CODE)
                    </label>
                    <input type="text" id="report-tracking-number" name="tracking_number" class="form-control" placeholder="e.g. DSK-019" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="report-asset-name" style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                        ASSET NAME
                    </label>
                    <input type="text" id="report-asset-name" name="asset_name" class="form-control" placeholder="e.g. Standing Desk #7" readonly style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #f8fafc;">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="issue_type" style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                        ISSUE TYPE
                    </label>
                    <select id="issue_type" name="issue_type" class="form-control" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="Hardware Malfunction">Hardware Malfunction</option>
                        <option value="Physical Damage">Physical Damage</option>
                        <option value="Missing Parts">Missing Parts</option>
                        <option value="Electrical Issue">Electrical Issue</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="image" style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                        SNAP / UPLOAD DAMAGE PHOTO
                    </label>
                    <input type="file" id="image" name="image" accept="image/*" class="form-control" style="width: 100%; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="description" style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                        ISSUE DESCRIPTION
                    </label>
                    <textarea id="description" name="description" rows="3" class="form-control" placeholder="Describe the damage or fault..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
                </div>

                <button type="submit" style="width: 100%; background-color: #4d7c0f; color: #fff; padding: 12px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">
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

                    if (response.ok && data.success && data.asset) {
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

            document.querySelectorAll('.resolve-btn').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const reportId = button.dataset.reportId;
                    const url = "{{ route('damage_queue.update_status', ['report' => 'TARGET_ID']) }}".replace('TARGET_ID', reportId);

                    try {
                        const response = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({ status: 'Resolved' })
                        });

                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert('Failed to resolve this report.');
                        }
                    } catch (error) {
                        console.error('Resolve Error:', error);
                        alert('Failed to resolve this report.');
                    }
                });
            });
        });
    </script>
</body>
</html>