# Use Case Diagram

Mermaid has no native UML use-case shape, so actors connect to stadium
(`([ ])`) nodes representing each use case. Every use case below maps to a
real route in `routes/web.php`; there is no use case here that isn't backed
by a working controller action and (per `tests/Feature/`) a passing test.

Guest-only use cases (Register, Log in) are outside both role subgraphs
since they exist before a role is established.

```mermaid
graph LR
    Guest((Guest))
    Staff((Staff))
    AssetTeam((Asset Team))

    Guest --> UC_Register(["Register account<br/>POST /register"])
    Guest --> UC_Login(["Log in<br/>POST /login"])

    subgraph SharedUC["Shared (any authenticated user)"]
        UC_Logout(["Log out<br/>POST /logout"])
        UC_ReportDamage(["Report a damaged asset<br/>(with photo)<br/>POST /damage-queue/report"])
        UC_ScanLookup(["Look up asset by<br/>tracking number / QR<br/>GET /api/assets/tracking/-trackingNumber-"])
    end

    subgraph StaffUC["Staff-only"]
        UC_StaffDash(["View Staff Dashboard<br/>(own reports + assigned assets)<br/>GET /staff_dashboard"])
    end

    subgraph AssetTeamUC["Asset Team-only"]
        UC_AssetDash(["View Asset Dashboard<br/>GET /dashboard"])
        UC_Directory(["View Asset Directory<br/>GET /assets"])
        UC_Assign(["Assign asset units<br/>to department/employee<br/>POST /assets/assign"])
        UC_CatCreate(["Create category<br/>POST /categories/store"])
        UC_CatRename(["Rename category<br/>PUT /categories/update"])
        UC_CatDelete(["Delete category<br/>DELETE /categories/delete"])
        UC_TagNext(["Get next tracking number<br/>GET /tags/next-number"])
        UC_TagBulk(["Bulk-generate asset tags<br/>POST /tags/bulk-generate"])
        UC_EditAsset(["Edit a single asset<br/>PUT /api/assets/-asset-"])
        UC_DeleteAsset(["Delete a single asset<br/>DELETE /api/assets/-asset-"])
        UC_AssetAnalysis(["View asset analysis<br/>GET /analysis/asset"])
        UC_LocAnalysis(["View location analysis<br/>GET /analysis/location"])
        UC_DamageQueue(["View damage queue<br/>GET /damage-queue"])
        UC_Resolve(["Resolve a damage report<br/>PATCH /damage-queue/-report-/status"])
        UC_LogHistory(["View audit log history<br/>GET /logs"])
    end

    Staff --> SharedUC
    Staff --> StaffUC
    AssetTeam --> SharedUC
    AssetTeam --> AssetTeamUC
```

## Notes grounded in the code, not assumption

- **Both roles can submit a damage report.** `report_damage.store` sits
  under plain `auth` middleware (no `role:` gate) because both
  `staff_dashboard.blade.php` and `damage_queue.blade.php` contain a submit
  form pointing at the same route — Staff self-reports on their own
  equipment, Asset Team can log a report on someone else's behalf from the
  management page.
- **The tracking-number lookup API is shared** for the same reason: it
  backs the QR scanner embedded in both the staff dashboard and the asset
  directory.
- **"Resolve" is the only status transition wired to the UI.** The
  `DamageReportStatus` enum defines `Pending`, `Under Inspection`, and
  `Resolved`, but `damage_queue.blade.php` only ever sends `Resolved` — the
  "Under Inspection" state exists in code but has no UI trigger. This is
  documented, not silently assumed to be reachable.
- **No use case exists for editing your own profile, password reset, or
  admin user management.** These are absent from `routes/web.php` entirely
  — not hidden behind a role, simply not built.
