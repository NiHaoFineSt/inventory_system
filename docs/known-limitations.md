# Known Limitations

Honest, verified state of the current build as of 2026-09-15
(`refactor/architecture-standard`, commit `59c0ddf`). Everything below was
checked against the running project, not assumed.

## Confirmed, not yet fixed

- **`public/storage` symlink does not exist.** Checked: `ls public/storage`
  returns "No such file or directory". Damage-report photos are stored
  successfully via `Storage::disk('public')->store('damage_photos', ...)`
  and the path is saved to `damage_reports.image_path`, but the file is
  **not reachable over HTTP** until `php artisan storage:link` is run once
  per environment (this is a one-time environment setup step, not a code
  bug — Laravel never creates this symlink automatically). No view
  currently renders the uploaded photo back to the user anyway, so this
  has had no visible symptom yet, but it will surface the moment a
  "view attached photo" feature is added.
- **No pagination anywhere.** `Asset::all()`
  (`DashboardController::assetDashboard`,
  `Services/Analytics/AssetAnalyticsService::build`,
  `Services/Analytics/LocationAnalyticsService::build`),
  `DamageReport::latest()->get()`
  (`DamageReportController::index`), and `AuditLog::latest()->get()`
  (`LogHistoryController::index`) all load their entire table into memory
  on every request. Fine at the current data volume (0 assets in the
  seeded database); will not scale past a few thousand rows without
  becoming a real performance problem.
- **`assets.tracking_number` → `damage_reports`/`audit_logs` is not a
  database foreign key**, just a matched string (see [erd.md](./erd.md)).
  Deleting an asset via `DELETE /api/assets/{asset}` leaves its damage
  reports and audit log entries in place, now referencing a
  `tracking_number` that no longer resolves to any asset.
- **`DamageReportStatus::UnderInspection` ("Under Inspection") is
  unreachable through the UI.** The enum defines it, but
  `damage_queue.blade.php`'s "Resolve" button only ever sends `Resolved` —
  confirmed by reading the button's JS handler. The status exists as a
  valid API value (`PATCH /damage-queue/{report}/status` accepts it) but
  no view offers it.
- **Dashboard filter dropdowns are decorative.** The filter controls in
  `asset_dashboard.blade.php` (`#filterYear`, `#filterMonth`, `#filterType`,
  `#filterAssigned`, `#filterStatus`) contain hardcoded demo options
  ("Monitor", "Chair", "Desk", "Laptop", "Head Office", "Branch A",
  "Branch B", "Available", "In Use") that don't correspond to real
  category/location/status values, and filter a client-side JS array, not
  the server-rendered data. This predates the 2026-09-15 remediation and
  was left as-is — it was not on the confirmed-fault list from the audit,
  and rebuilding it as data-driven filtering is a feature change, not a
  bug fix.

## Deliberately absent (not a gap — never built)

- Password reset / forgot-password flow.
- Email verification (the `email_verified_at` column exists on `users` but
  nothing sets or checks it).
- User profile self-editing.
- Admin user management (promote/demote roles, deactivate accounts).
- A full status-history or comment thread on a damage report — just the
  one `status` column and a single "Resolve" transition.

## Deliberate design choices worth knowing before extending

- **SQLite is the only configured connection** (`config/database.php`
  default, `DB_CONNECTION=sqlite` in `.env`). `AssetAnalyticsService` and
  `LocationAnalyticsService` were rewritten during the 2026-09-15
  remediation specifically to avoid MySQL-only SQL (`DATEDIFF`, `NOW()`)
  that the dead `AssetAnalysisController` this replaced had used — that
  code would have thrown immediately on this connection if it had ever
  been wired up.
- **`lockForUpdate()` in `AssetService::assign()` is a no-op on SQLite**
  (no row-level locking support in SQLite's grammar). The real correctness
  guarantee comes from wrapping the read-then-update in
  `DB::transaction()`, not from the lock itself. Moving to MySQL/Postgres
  would make the lock meaningful without any code change.
- **Bulk tag generation trusts the client-supplied `starting_number`**
  rather than recomputing it inside the transaction. A `tracking_number`
  collision fails the entire batch atomically via the unique constraint
  (see [data-flow-diagrams.md](./data-flow-diagrams.md#2-tag-generation-bulk))
  rather than silently skipping and drifting numbers, which is what the
  pre-remediation code did.
