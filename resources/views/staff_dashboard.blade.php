<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Asset Management System</title>
    @vite(['resources/css/staff_dashboard.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>

<!-- NAVIGATION HEADER WITH LOGO & SIGN OUT -->
<nav class="navbar">
    <div class="nav-brand">
        <div class="nav-logo">
            <img src="{{ asset('logo.jpg') }}" style="width: 80px; height: auto;" alt="Logo">
        </div>
        <span class="nav-title">Asset Tracker</span>
    </div>
    
    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" class="btn-signout">
            <span class="material-icons-outlined">logout</span>
            Sign Out
        </button>
    </form>
</nav>

<div class="container">
    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- PROFILE CARD WITH ASSIGNED ASSETS -->
    <section class="profile-card">
        <div class="profile-details">
            <h2>{{ $user->name }}</h2>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Role:</strong> {{ $user->role->label() }}</p>
            <p><strong>Assigned Assets:</strong>
                @if($assignedAssets->count() > 0)
                    <span class="badge-asset">
                        {{ $assignedAssets->pluck('asset_name')->implode(', ') }}
                    </span>
                @else
                    <span class="badge-asset">No assets currently assigned</span>
                @endif
            </p>
        </div>
        <button class="report-btn" onclick="openReportModal()">
            <span class="material-icons-outlined">qr_code_scanner</span> Report Asset Damage
        </button>
    </section>

    <!-- DAMAGE REPORT HISTORY TABLE -->
    <section class="history-card">
        <div class="history-header">My Damage Report History</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Tracking #</th>
                        <th>Asset Name</th>
                        <th>Issue Type</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($report->created_at ?? now())->format('Y-m-d H:i') }}</td>
                            <td><strong>{{ $report->tracking_number ?? 'N/A' }}</strong></td>
                            <td>{{ $report->asset_name ?? 'N/A' }}</td>
                            <td>{{ $report->issue_type ?? 'N/A' }}</td>
                            <td>{{ $report->description ?? 'N/A' }}</td>
                            <td>
                                @php $st = $report->status?->value ?? 'Pending'; @endphp
                                <span class="status-badge {{ $st === 'Resolved' ? 'status-approved' : 'status-pending' }}">
                                    {{ $st }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">No damage reports submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- REPORT DAMAGE MODAL -->
<div id="reportModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Report Asset Damage</h3>
            <span class="close-btn" onclick="closeReportModal()">&times;</span>
        </div>
        
        <div id="qr-reader"></div>

        <form action="{{ route('report_damage.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="tracking_number">Tracking Number / QR Code</label>
                <input type="text" id="tracking_number" name="tracking_number" required placeholder="Scan QR or enter tracking number">
            </div>

            <div class="form-group">
                <label for="asset_name">Asset Name</label>
                <input type="text" id="asset_name" name="asset_name" required placeholder="Auto-filled or manual entry">
            </div>

            <div class="form-group">
                <label for="issue_type">Issue Type</label>
                <select id="issue_type" name="issue_type" required>
                    <option value="">Select Issue</option>
                    <option value="Hardware Breakdown">Hardware Breakdown</option>
                    <option value="Physical Damage">Physical Damage</option>
                    <option value="Software Issue">Software Issue</option>
                    <option value="Missing Parts">Missing Parts</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required placeholder="Describe the damage..."></textarea>
            </div>

            <div class="form-group">
                <label for="image">Upload Photo (Optional)</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewDamageImage(event)">
                <div id="imagePreviewContainer" class="image-preview-container">
                    <img id="imagePreview" alt="Damage Preview">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeReportModal()">Cancel</button>
                <button type="submit" class="btn-submit">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
let html5QrcodeScanner;

function openReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) modal.style.display = 'flex';
    
    html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 220 });
    html5QrcodeScanner.render(onScanSuccess);
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) modal.style.display = 'none';
    
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
}

function fetchAssetDetails(trackingNum) {
    if (!trackingNum || trackingNum.trim() === '') {
        document.getElementById('asset_name').value = '';
        return;
    }

    let cleanTrackingNum = trackingNum.trim();
    if (cleanTrackingNum.includes('|')) {
        cleanTrackingNum = cleanTrackingNum.split('|')[0].trim();
    }

    fetch(`/api/assets/tracking/${encodeURIComponent(cleanTrackingNum)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Asset not found');
        return response.json();
    })
    .then(data => {
        if (data && data.success && data.asset) {
            document.getElementById('asset_name').value = data.asset.asset_name || data.asset.name || '';
        } else if (data && (data.asset_name || data.name)) {
            document.getElementById('asset_name').value = data.asset_name || data.name || '';
        } else {
            document.getElementById('asset_name').value = '';
        }
    })
    .catch(() => {
        document.getElementById('asset_name').value = '';
    });
}

function onScanSuccess(decodedText) {
    let trackingNum = decodedText;
    if (decodedText.includes('/scan-asset/')) {
        trackingNum = decodedText.split('/scan-asset/')[1].split('?')[0];
    }
    document.getElementById('tracking_number').value = trackingNum;
    fetchAssetDetails(trackingNum);

    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const trackingInput = document.getElementById('tracking_number');
    if (trackingInput) {
        trackingInput.addEventListener('input', function () {
            fetchAssetDetails(this.value);
        });

        let timer;
        trackingInput.addEventListener('keyup', function () {
            clearTimeout(timer);
            timer = setTimeout(() => fetchAssetDetails(this.value), 300);
        });
    }
});

function previewDamageImage(event) {
    const reader = new FileReader();
    reader.onload = function () {
        const output = document.getElementById('imagePreview');
        const container = document.getElementById('imagePreviewContainer');
        output.src = reader.result;
        container.style.display = 'block';
    };
    if (event.target.files && event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>

</body>
</html>