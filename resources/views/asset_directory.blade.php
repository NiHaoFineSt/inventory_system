<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Asset Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @vite(['resources/css/asset_directory.css', 'resources/css/tag_generator.css'])
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
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
                <a href="{{ route('asset_directory') }}" class="nav-btn {{ request()->routeIs('asset_directory') ? 'active' : '' }}" style="{{ request()->routeIs('asset_directory') ? 'pointer-events: none; cursor: default;' : '' }}">
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

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">

            <header class="top-banner no-print" style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Asset Directory</h1>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-action" style="background-color: #0284c7; color: #fff; padding: 10px 16px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;" onclick="openModal('addCategoryModal')">
                        <span class="material-icons-outlined">category</span> Manage Categories
                    </button>
                    <button type="button" class="btn-action" style="background-color: #2563eb; color: #fff; padding: 10px 16px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;" onclick="openModal('addAssetModal')">
                        <span class="material-icons-outlined">add</span> Add Asset / Generate Tags
                    </button>
                </div>
            </header>

            <!-- AXIS FILTERS BAR -->
            <section class="filter-bar no-print">
                <div class="filter-group">
                    <label>Category</label>
                    <select id="yAxisFilter" onchange="onCategoryChange()">
                        <option value="all">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select id="xAxisFilter" onchange="filterDirectoryTable()">
                        <option value="all">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->asset_name }}" data-category="{{ $type->category }}">{{ $type->asset_name }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <!-- DIRECTORY SUMMARY TABLE -->
            <section class="table-card">
                <table class="data-table" id="directoryTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Assigned / Total</th>
                            <th>Pending Actions</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaryData as $row)
                            <tr data-category="{{ $row->category }}" data-type="{{ $row->asset_name }}">
                                <td><strong>{{ $row->category }}</strong></td>
                                <td>
                                    <a href="javascript:void(0)" 
                                       onclick="openItemDetailModal('{{ $row->asset_name }}', '{{ $row->category }}')"
                                       style="color: #2563eb; font-weight: 600; text-decoration: none; border-bottom: 1px dashed #2563eb;">
                                        {{ $row->asset_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge active-ratio">
                                        {{ $row->assigned_count ?? 0 }}/{{ $row->total_count }} Assigned
                                    </span>
                                </td>
                                <td>
                                    @if($row->pending_count > 0)
                                        <span class="badge action-needed">{{ $row->pending_count }} Pending Repairs</span>
                                    @else
                                        <span class="badge action-ok">0 Pending Actions</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-action" style="background-color: #0284c7; color: #fff; padding: 6px 12px; border: none; border-radius: 4px; font-weight: 500; cursor: pointer;"
                                            onclick="openAssignModal('{{ $row->asset_name }}', '{{ $row->category }}', {{ $row->unassigned_count ?? $row->total_count }})">
                                        <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">assignment</span> Assign
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 24px;">
                                    No assets found in the directory.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

        </main>

    </div>

    <!-- 1. CATEGORY MANAGEMENT MODAL -->
    <div id="addCategoryModal" class="modal-overlay" style="display: none;">
        <div class="modal-card" style="max-width: 480px; width: 100%;">
            <div class="modal-header">
                <div>
                    <h3>Manage Categories</h3>
                    <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Add new categories or manage existing ones across the system.</p>
                </div>
                <span class="material-icons-outlined close-btn" onclick="closeModal('addCategoryModal')">close</span>
            </div>

            <!-- Add Category Input Section -->
            <form id="addCategoryForm" onsubmit="submitNewCategory(event)">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="new-category-name" style="font-weight: 600;">Add New Category <span style="color: #ef4444;">*</span></label>
                    <div style="display: flex; gap: 8px; margin-top: 4px;">
                        <input type="text" id="new-category-name" class="form-control" placeholder="e.g. Network Equipment, Furniture" required style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <button type="submit" id="btnSubmitCategory" class="btn-action" style="background-color: #0284c7; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                            Add
                        </button>
                    </div>
                </div>
            </form>

            <hr style="margin: 16px 0; border: none; border-top: 1px solid #e2e8f0;">

            <!-- Previous Categories List -->
            <div style="margin-top: 10px;">
                <h4 style="font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 10px;">Existing Categories</h4>
                
                <div style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                                <th style="padding: 10px 12px; color: #64748b;">Category Name</th>
                                <th style="padding: 10px 12px; text-align: right; color: #64748b; width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoryListBody">
                            @foreach($categories as $cat)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 12px;">
                                        <span class="cat-display-text">{{ $cat }}</span>
                                        <input type="text" class="cat-edit-input" value="{{ $cat }}" style="display: none; width: 100%; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px;">
                                    </td>
                                    <td style="padding: 8px 12px; text-align: right; white-space: nowrap;">
                                        <!-- Edit Button -->
                                        <button type="button" class="btn-cat-edit" onclick="enableCategoryEdit(this)" style="background: transparent; color: #d97706; border: none; cursor: pointer; padding: 2px 4px; margin-right: 4px;" title="Edit">
                                            <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle;">edit</span>
                                        </button>
                                        
                                        <!-- Save Button (Hidden initially) -->
                                        <button type="button" class="btn-cat-save" onclick="saveCategoryEdit(this, '{{ $cat }}')" style="display: none; background: transparent; color: #16a34a; border: none; cursor: pointer; padding: 2px 4px; margin-right: 4px;" title="Save">
                                            <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle;">check</span>
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" class="btn-cat-delete" onclick="deleteCategory('{{ $cat }}')" style="background: transparent; color: #dc2626; border: none; cursor: pointer; padding: 2px 4px;" title="Delete">
                                            <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle;">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 16px; text-align: right;">
                <button type="button" class="btn-action" onclick="closeModal('addCategoryModal')" style="padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- 2. ADD ASSET & GENERATE TAGS MODAL -->
    <div id="addAssetModal" class="modal-overlay" style="display: none;">
        <div class="modal-card modal-large" style="max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <div>
                    <h3>Generate Asset Tags</h3>
                    <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Add new assets directly to the directory and produce printable QR tags.</p>
                </div>
                <span class="material-icons-outlined close-btn" onclick="closeModal('addAssetModal')">close</span>
            </div>

            <div class="modal-body" style="padding: 16px 0;">
                <form id="generator-form" onsubmit="generateAndSaveTags(event)">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="asset-category">Asset Category</label>
                            <select id="asset-category" class="form-control">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="asset-type">Asset Type / Name</label>
                            <input type="text" id="asset-type" class="form-control" placeholder="e.g. Ergonomic Chair, MacBook Pro" required>
                        </div>

                        <div class="form-group">
                            <label for="prefix">Asset Code Prefix</label>
                            <input type="text" id="prefix" class="form-control" placeholder="e.g. DSK-, MON-, LAP-" oninput="fetchNextStartingNumber(this.value)" required>
                        </div>

                        <div class="form-group">
                            <label for="start-num">Starting Number</label>
                            <input type="number" id="start-num" class="form-control" value="1" min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Total Tags / Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" required>
                        </div>

                    </div>

                    <div class="form-actions" style="margin-top: 16px; display: flex; gap: 10px;">
                        <button type="submit" class="primary-btn" id="submit-btn" style="flex: 1;">
                            <span class="material-icons-outlined">qr_code</span> Generate & Add to Directory
                        </button>
                        <button type="button" class="print-btn" onclick="window.print()">
                            <span class="material-icons-outlined">print</span> Print Tag Sheet
                        </button>
                    </div>
                </form>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

                <section class="print-sheet-container">
                    <div class="sheet-header" style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <span class="badge" id="tag-count-badge">0 Tags Generated</span>
                        <p class="print-tip" style="font-size: 12px; color: #64748b; margin: 0;"><span class="material-icons-outlined icon" style="font-size: 14px; vertical-align: middle;">info</span> Ready for printing on standard A4 / Sticker sheets.</p>
                    </div>

                    <div class="tags-grid" id="tags-grid">
                        <!-- Dynamic QR Tags injected via JavaScript -->
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- 3. CAMERA SCANNER & REAL-TIME RESULT MODAL -->
    <div id="scannerModal" class="modal-overlay" style="display: none;">
        <div class="modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <div>
                    <h3>QR Code Scanner</h3>
                    <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Scan QR tag to display live assignment status.</p>
                </div>
                <span class="material-icons-outlined close-btn" onclick="closeScannerModal()">close</span>
            </div>

            <div class="modal-body" style="padding: 16px 0;">
                <div id="reader" style="width: 100%; min-height: 250px; background: #f8fafc; border-radius: 8px; overflow: hidden;"></div>
                
                <!-- LIVE SCAN RESULT DISPLAY -->
                <div id="asset-details-box" style="display: none; margin-top: 16px; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h4 style="margin: 0; font-size: 15px; color: #0f172a;" id="scanned-asset-name">Asset Details</h4>
                        <span id="scanned-assignment-badge" class="badge" style="font-size: 11px; padding: 3px 8px;">-</span>
                    </div>

                    <p style="margin: 4px 0; font-size: 12px; color: #475569;"><strong>Tracking Code:</strong> <span id="scanned-tracking" style="color: #2563eb; font-weight: 600;">-</span></p>
                    <p style="margin: 4px 0; font-size: 12px; color: #475569;"><strong>Category:</strong> <span id="scanned-category">-</span></p>
                    <p style="margin: 4px 0; font-size: 12px; color: #475569;"><strong>Location / Assigned To:</strong> <span id="scanned-location" style="font-weight: 600;">-</span></p>
                    <p style="margin: 4px 0; font-size: 12px; color: #475569;"><strong>Asset Condition:</strong> <span id="scanned-condition">-</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. VIEW ALL QR TAGS MODAL (TRIGGERED BY TYPE CLICK) -->
    <div id="itemDetailModal" class="modal-overlay" style="display: none;">
        <div class="modal-card modal-large" style="max-height: 90vh; overflow-y: auto; width: 90%; max-width: 950px;">
            <div class="modal-header">
                <div>
                    <h3 id="detailModalTitle">Asset QR List</h3>
                    <p id="detailModalSubtitle" style="font-size: 12px; color: #64748b; margin-top: 2px;">Manage individual generated tags, edit details, or remove assets.</p>
                </div>
                <span class="material-icons-outlined close-btn" onclick="closeModal('itemDetailModal')">close</span>
            </div>

            <div class="modal-body" style="padding: 16px 0;">
                <div id="type-tags-grid" style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: center;">
                    <!-- QR Cards injected via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- 5. EDIT ASSET SINGLE ITEM MODAL -->
    <div id="editAssetModal" class="modal-overlay" style="display: none;">
        <div class="modal-card" style="max-width: 450px;">
            <div class="modal-header">
                <div>
                    <h3>Edit Asset Tag</h3>
                    <p id="editTrackingBadge" style="font-size: 12px; color: #2563eb; font-weight: 600; margin-top: 2px;"></p>
                </div>
                <span class="material-icons-outlined close-btn" onclick="closeModal('editAssetModal')">close</span>
            </div>

            <form id="editAssetForm" onsubmit="submitEditAsset(event)">
                <input type="hidden" id="edit-asset-id">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit-asset-name">Asset Name</label>
                    <input type="text" id="edit-asset-name" class="form-control" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit-location">Location / Assigned To</label>
                    <input type="text" id="edit-location" class="form-control" placeholder="e.g. Unassigned, HR Dept, Office A">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit-status">Status</label>
                    <select id="edit-status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Disposed">Disposed</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="edit-condition">Condition</label>
                    <select id="edit-condition" class="form-control">
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Damaged">Damaged</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn-action" style="flex: 1; background-color: #2563eb; color: #fff; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        Save Changes
                    </button>
                    <button type="button" class="btn-action" onclick="closeModal('editAssetModal')" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. ASSIGN ASSET MODAL -->
    <div id="assignAssetModal" class="modal-overlay" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="assignModalTitle">Assign Assets</h3>
                    <p id="assignModalSubtitle" style="font-size: 12px; color: #64748b; margin-top: 2px;"></p>
                </div>
                <span class="material-icons-outlined close-btn" onclick="closeModal('assignAssetModal')">close</span>
            </div>

            <form id="assignForm" onsubmit="submitAssignment(event)">
                <input type="hidden" id="assignAssetName">
                <input type="hidden" id="assignCategory">

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="assignDepartment">Target Department</label>
                    <select id="assignDepartment" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <option value="IT Department">IT Department</option>
                        <option value="HR Department">HR Department</option>
                        <option value="Finance Department">Finance Department</option>
                        <option value="Operations">Operations</option>
                        <option value="Head Office">Head Office</option>
                    </select>
                </div>

                <div class="form-group" id="employeeFieldGroup" style="display: none; margin-bottom: 12px;">
                    <label for="assignEmployee">Employee Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="assignEmployee" class="form-control" placeholder="e.g. John Doe">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="assignQuantity">Quantity to Assign</label>
                    <input type="number" id="assignQuantity" class="form-control" min="1" value="1" required>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" id="btnSubmitAssign" class="btn-action" style="flex: 1; background-color: #2563eb; color: #fff; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        Confirm Assignment
                    </button>
                    <button type="button" class="btn-action" onclick="closeModal('assignAssetModal')" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT SECTION -->
    <script>
        let html5QrcodeScanner = null;
        let currentOpenAssetName = '';
        let currentOpenCategory = '';

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            if (id === 'addAssetModal') {
                const initialPrefix = document.getElementById('prefix').value;
                fetchNextStartingNumber(initialPrefix);
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        /* --- CATEGORY MANAGEMENT HANDLERS --- */
        
        // 1. Add New Category
        async function submitNewCategory(event) {
            event.preventDefault();
            
            const categoryInput = document.getElementById('new-category-name');
            const categoryName = categoryInput.value.trim();
            const btnSubmit = document.getElementById('btnSubmitCategory');

            if (!categoryName) return;

            btnSubmit.disabled = true;
            btnSubmit.innerText = '...';

            try {
                const response = await fetch("{{ route('categories.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ category_name: categoryName })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    location.reload();
                } else {
                    alert("Error: " + (result.message || "Failed to save category."));
                }
            } catch (error) {
                console.error("Add Category Error:", error);
                alert("An error occurred while communicating with the server.");
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Add';
            }
        }

        // 2. Enable Edit Mode
        function enableCategoryEdit(btn) {
            const row = btn.closest('tr');
            row.querySelector('.cat-display-text').style.display = 'none';
            row.querySelector('.cat-edit-input').style.display = 'block';

            btn.style.display = 'none';
            row.querySelector('.btn-cat-save').style.display = 'inline-block';
        }

        // 3. Save Category Edit
        async function saveCategoryEdit(btn, oldName) {
            const row = btn.closest('tr');
            const newName = row.querySelector('.cat-edit-input').value.trim();

            if (!newName) {
                alert('Category name cannot be empty.');
                return;
            }

            try {
                const response = await fetch("{{ route('categories.update') }}", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ old_name: oldName, new_name: newName })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    location.reload();
                } else {
                    alert("Error: " + (result.message || "Failed to update category."));
                }
            } catch (error) {
                console.error("Update Category Error:", error);
                alert("An error occurred while updating the category.");
            }
        }

        // 4. Delete Category
        async function deleteCategory(categoryName) {
            if (!confirm(`Are you sure you want to delete "${categoryName}" and all associated assets?`)) {
                return;
            }

            try {
                const response = await fetch("{{ route('categories.destroy') }}", {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ category_name: categoryName })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    location.reload();
                } else {
                    alert("Error: " + (result.message || "Failed to delete category."));
                }
            } catch (error) {
                console.error("Delete Category Error:", error);
                alert("An error occurred while deleting the category.");
            }
        }

        /* --- QR CAMERA SCANNER LOGIC --- */
        function openScannerModal() {
            document.getElementById('scannerModal').style.display = 'flex';
            document.getElementById('asset-details-box').style.display = 'none';

            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                    fps: 10, 
                    qrbox: { width: 220, height: 220 },
                    aspectRatio: 1.0
                }, false);
            }

            html5QrcodeScanner.render(onScanSuccess, (error) => {
                // Listening...
            });
        }

        async function onScanSuccess(decodedText) {
            let trackingNumber = decodedText.includes('|') ? decodedText.split('|')[0].trim() : decodedText.trim();

            try {
                const response = await fetch(`/api/assets/tracking/${encodeURIComponent(trackingNumber)}`);
                const data = await response.json();

                if (data.success && data.asset) {
                    const asset = data.asset;
                    
                    document.getElementById('scanned-asset-name').innerText = asset.asset_name;
                    document.getElementById('scanned-tracking').innerText = asset.tracking_number;
                    document.getElementById('scanned-category').innerText = asset.category ?? 'N/A';
                    document.getElementById('scanned-location').innerText = asset.location ?? 'Unassigned';
                    document.getElementById('scanned-condition').innerText = asset.condition ?? 'Good';

                    const badge = document.getElementById('scanned-assignment-badge');
                    if (asset.assignment_status === 'Assigned') {
                        badge.innerText = 'Assigned';
                        badge.style.backgroundColor = '#dcfce7';
                        badge.style.color = '#15803d';
                    } else {
                        badge.innerText = 'Unassigned';
                        badge.style.backgroundColor = '#f1f5f9';
                        badge.style.color = '#64748b';
                    }
                    
                    document.getElementById('asset-details-box').style.display = 'block';
                } else {
                    alert(`Asset code "${trackingNumber}" not found.`);
                }
            } catch (err) {
                console.error("Scan lookup error:", err);
                alert("Failed to fetch live asset details.");
            }
        }

        function closeScannerModal() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(err => console.error(err));
            }
            closeModal('scannerModal');
        }

        /* --- TYPE POPUP & QR GALLERY LOGIC --- */
        async function openItemDetailModal(assetName, category) {
            currentOpenAssetName = assetName;
            currentOpenCategory = category;

            document.getElementById('detailModalTitle').innerText = `${assetName} (${category})`;
            document.getElementById('itemDetailModal').style.display = 'flex';

            const grid = document.getElementById('type-tags-grid');
            grid.innerHTML = '<p style="color: #64748b;">Loading QR tags...</p>';

            try {
                const url = `/api/assets/by-type?asset_name=${encodeURIComponent(assetName)}&category=${encodeURIComponent(category)}`;
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    const errText = await response.text();
                    console.error(`API Error ${response.status}:`, errText);
                    grid.innerHTML = `<p style="color: #ef4444;">Server error (${response.status}). Please try again.</p>`;
                    return;
                }

                const data = await response.json();

                if (data.success && data.assets) {
                    renderTypeQRCards(data.assets);
                } else {
                    grid.innerHTML = '<p style="color: #ef4444;">Failed to load assets.</p>';
                }
            } catch (err) {
                console.error("Error loading QR tags:", err);
                grid.innerHTML = '<p style="color: #ef4444;">Network connection or parsing error.</p>';
            }
        }

        function renderTypeQRCards(assets) {
            const grid = document.getElementById('type-tags-grid');
            grid.innerHTML = '';

            if (assets.length === 0) {
                grid.innerHTML = '<p style="color: #94a3b8;">No asset tags found for this item.</p>';
                return;
            }

            assets.forEach((asset, index) => {
                const qrContainerId = `detail-qr-box-${index}`;
                const card = document.createElement('div');
                card.style.cssText = 'border: 1px solid #cbd5e1; padding: 14px; border-radius: 8px; background: #fff; text-align: center; width: 200px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);';

                const locationDisplay = asset.location ? asset.location : 'Unassigned';
                const isAssigned = asset.location && asset.location.toLowerCase() !== 'unassigned';

                card.innerHTML = `
                    <div style="font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 4px;">ASSET TAG</div>
                    <div id="${qrContainerId}" style="display: flex; justify-content: center; align-items: center; min-height: 110px; margin: 6px 0;"></div>
                    <div style="font-weight: 700; font-size: 13px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${asset.asset_name}</div>
                    <div style="font-size: 11px; font-weight: 700; color: #2563eb; margin: 2px 0;">${asset.tracking_number}</div>
                    <div style="font-size: 10px; color: ${isAssigned ? '#16a34a' : '#64748b'}; font-weight: 600; margin-bottom: 8px;">
                        ${locationDisplay}
                    </div>
                    
                    <div style="display: flex; gap: 4px; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                        <button type="button" onclick="openEditAssetModal(${JSON.stringify(asset).replace(/"/g, '&quot;')})" 
                                style="flex: 1; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 4px 0; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 2px;">
                            <span class="material-icons-outlined" style="font-size: 14px;">edit</span> Edit
                        </button>
                        <button type="button" onclick="deleteSingleAsset(${asset.id}, '${asset.tracking_number}')" 
                                style="flex: 1; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 0; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 2px;">
                            <span class="material-icons-outlined" style="font-size: 14px;">delete</span> Delete
                        </button>
                    </div>
                `;

                grid.appendChild(card);

                if (typeof QRCode !== 'undefined') {
                    new QRCode(document.getElementById(qrContainerId), {
                        text: String(asset.tracking_number),
                        width: 100,
                        height: 100,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            });
        }

        /* --- EDIT & DELETE SINGLE ASSET HANDLERS --- */
        function openEditAssetModal(asset) {
            document.getElementById('edit-asset-id').value = asset.id;
            document.getElementById('editTrackingBadge').innerText = `Tag: ${asset.tracking_number}`;
            document.getElementById('edit-asset-name').value = asset.asset_name;
            document.getElementById('edit-location').value = asset.location ?? '';
            document.getElementById('edit-status').value = asset.status ?? 'Active';
            document.getElementById('edit-condition').value = asset.condition ?? 'Good';

            openModal('editAssetModal');
        }

        async function submitEditAsset(event) {
            event.preventDefault();
            const id = document.getElementById('edit-asset-id').value;

            const payload = {
                asset_name: document.getElementById('edit-asset-name').value.trim(),
                location: document.getElementById('edit-location').value.trim() || 'Unassigned',
                status: document.getElementById('edit-status').value,
                condition: document.getElementById('edit-condition').value,
            };

            try {
                const response = await fetch(`/api/assets/${id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    closeModal('editAssetModal');
                    openItemDetailModal(currentOpenAssetName, currentOpenCategory);
                } else {
                    alert("Update failed: " + (data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error("Update asset error:", err);
                alert("Failed to submit update.");
            }
        }

        async function deleteSingleAsset(id, trackingNumber) {
            if (!confirm(`Are you sure you want to delete tracking code ${trackingNumber}?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/assets/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    openItemDetailModal(currentOpenAssetName, currentOpenCategory);
                } else {
                    alert("Delete failed: " + (data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error("Delete asset error:", err);
                alert("Failed to delete asset.");
            }
        }

        /* --- FILTERS & GENERATION AUXILIARY LOGIC --- */
        function onCategoryChange() {
            const selectedCategory = document.getElementById('yAxisFilter').value;
            const typeSelect = document.getElementById('xAxisFilter');
            const typeOptions = typeSelect.querySelectorAll('option');

            typeOptions.forEach(option => {
                if (option.value === 'all') {
                    option.style.display = 'block';
                    return;
                }
                const match = selectedCategory === 'all' || option.getAttribute('data-category') === selectedCategory;
                option.style.display = match ? 'block' : 'none';
            });

            typeSelect.value = 'all';
            filterDirectoryTable();
        }

        function filterDirectoryTable() {
            const category = document.getElementById('yAxisFilter').value;
            const type = document.getElementById('xAxisFilter').value;
            const rows = document.querySelectorAll('#directoryTable tbody tr[data-category]');

            rows.forEach(row => {
                const matchCategory = (category === 'all' || row.getAttribute('data-category') === category);
                const matchType = (type === 'all' || row.getAttribute('data-type') === type);
                row.style.display = (matchCategory && matchType) ? '' : 'none';
            });
        }

        function openAssignModal(assetName, category, availableCount) {
            document.getElementById('assignAssetName').value = assetName;
            document.getElementById('assignCategory').value = category;
            document.getElementById('assignModalTitle').innerText = `Assign ${assetName}`;
            document.getElementById('assignModalSubtitle').innerText = `${availableCount} unit(s) available for allocation.`;

            const employeeGroup = document.getElementById('employeeFieldGroup');
            const employeeInput = document.getElementById('assignEmployee');
            
            if (category === 'IT Hardware') {
                employeeGroup.style.display = 'block';
                employeeInput.setAttribute('required', 'required');
            } else {
                employeeGroup.style.display = 'none';
                employeeInput.removeAttribute('required');
            }

            openModal('assignAssetModal');
        }

        async function fetchNextStartingNumber(prefix) {
            const startNumInput = document.getElementById('start-num');
            if (!prefix.trim()) {
                startNumInput.value = 1;
                return;
            }

            try {
                const response = await fetch(`{{ route('tags.next_number') }}?prefix=${encodeURIComponent(prefix.trim())}`);
                const data = await response.json();
                
                if (data.success && data.next_number) {
                    startNumInput.value = data.next_number;
                }
            } catch (error) {
                console.error("Error fetching starting number:", error);
            }
        }

        async function generateAndSaveTags(event) {
            if (event) event.preventDefault();

            const submitBtn = document.getElementById('submit-btn');
            if (!submitBtn) return;

            submitBtn.disabled = true;
            submitBtn.innerText = 'Saving to Database...';

            const payload = {
                category: document.getElementById('asset-category').value,
                asset_name: document.getElementById('asset-type').value.trim(),
                prefix: document.getElementById('prefix').value.trim().toUpperCase(),
                starting_number: parseInt(document.getElementById('start-num').value, 10) || 1,
                quantity: parseInt(document.getElementById('quantity').value, 10) || 1,
                location: null
            };

            try {
                const response = await fetch("{{ route('generate_bulk_tags') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && (result.status === 'success' || result.success)) {
                    renderTags(result.data || []);
                } else {
                    let errorMsg = result.message || "Failed to save tags.";
                    if (result.errors) {
                        errorMsg += "\n" + Object.values(result.errors).flat().join("\n");
                    }
                    alert("Error:\n" + errorMsg);
                }
            } catch (error) {
                console.error("Save Error:", error);
                alert("An error occurred while communicating with the server.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-icons-outlined">qr_code</span> Generate & Add to Directory';
            }
        }

        function renderTags(assets) {
            const grid = document.getElementById('tags-grid');
            const badge = document.getElementById('tag-count-badge');
            if (!grid) return;

            grid.innerHTML = '';

            if (!Array.isArray(assets) || assets.length === 0) {
                if (badge) badge.innerText = `0 Tags Generated`;
                return;
            }

            assets.forEach((asset, index) => {
                const qrBoxId = `qr-box-${index}`;
                const tagCard = document.createElement('div');
                tagCard.className = 'asset-tag-card';
                tagCard.style.cssText = 'border: 1px solid #cbd5e1; padding: 12px; border-radius: 8px; background: #fff; text-align: center; width: 180px; display: inline-block; margin: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);';

                tagCard.innerHTML = `
                    <div style="font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">ASSET TRACKER</div>
                    <div id="${qrBoxId}" style="display: flex; justify-content: center; align-items: center; min-height: 120px; margin: 6px 0; background: #ffffff; padding: 6px; border-radius: 4px;"></div>
                    <div style="font-weight: 700; font-size: 12px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${asset.asset_name}</div>
                    <div style="font-size: 11px; font-weight: 600; color: #2563eb; margin-top: 2px;">${asset.tracking_number}</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">${asset.location ?? 'Unassigned'}</div>
                `;
                grid.appendChild(tagCard);

                if (typeof QRCode !== 'undefined') {
                    const qrContainer = document.getElementById(qrBoxId);
                    qrContainer.innerHTML = '';
                    new QRCode(qrContainer, {
                        text: String(asset.tracking_number),
                        width: 110,
                        height: 110,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } else {
                    document.getElementById(qrBoxId).innerText = '[QR Code Error]';
                }
            });

            if (badge) {
                badge.innerText = `${assets.length} Tags Generated`;
            }
        }

        async function submitAssignment(event) {
            event.preventDefault();

            const payload = {
                asset_name: document.getElementById('assignAssetName').value,
                category: document.getElementById('assignCategory').value,
                department: document.getElementById('assignDepartment').value,
                employee: document.getElementById('assignEmployee').value.trim(),
                quantity: parseInt(document.getElementById('assignQuantity').value, 10) || 1
            };

            try {
                const response = await fetch("{{ route('assets.assign') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert("Assignment Error:\n" + (result.message || "Failed to assign assets."));
                }
            } catch (error) {
                console.error("Assign Error:", error);
                alert("An error occurred while submitting assignment.");
            }
        }
    </script>
</body>

</html>