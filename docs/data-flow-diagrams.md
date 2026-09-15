# Data Flow Diagrams

Mermaid has no native DFD notation, so these use flowchart conventions:
rounded rectangles = external entities, rectangles = processes, cylinders =
data stores. Each diagram traces one real code path — file and method names
are named inline so any step can be checked against source.

## 1. Authentication (login)

Traced from `App\Http\Controllers\AuthController::login()`.

```mermaid
flowchart TD
    U([User]) -->|"email, password, role"| LR["LoginRequest<br/>validates shape"]
    LR --> ATTEMPT{"Auth::attempt<br/>(email, password)"}
    ATTEMPT -- fails --> ERR1["back() with<br/>'credentials do not match'"] --> U
    ATTEMPT -- succeeds --> REGEN["session regenerate"]
    REGEN --> ROLECHK{"user.role ==<br/>selected role?"}
    ROLECHK -- no --> LOGOUT["Auth::logout()"] --> ERR2["back() with<br/>'does not have permission'"] --> U
    ROLECHK -- yes --> DECIDE{"role enum"}
    DECIDE -- AssetTeam --> R1["redirect: asset_dashboard"] --> U
    DECIDE -- Staff --> R2["redirect: staff_dashboard"] --> U

    USERS[(users table)]
    ATTEMPT -.reads.-> USERS
```

## 2. Tag generation (bulk)

Traced from `App\Http\Controllers\TagGeneratorController::storeBulkTags()`
→ `App\Services\TagGeneratorService::storeBulk()`.

```mermaid
flowchart TD
    AT([Asset Team user]) -->|"category, asset_name, prefix,<br/>starting_number, quantity, location"| GTR["GenerateTagsRequest<br/>validates shape"]
    GTR --> TXN["DB::transaction starts"]
    TXN --> LOOP["for i in 1..quantity:<br/>build tracking_number =<br/>prefix + zero-padded number"]
    LOOP --> CREATE["Asset::create<br/>(status=Active, condition=Good,<br/>qr_code_payload=tracking_number)"]
    CREATE --> ASSETS[(assets table)]
    CREATE -->|unique constraint violated| ROLLBACK["transaction rolls back,<br/>whole batch fails atomically"]
    ROLLBACK --> ERR["422: 'tracking numbers<br/>already exist, refresh and retry'"] --> AT
    LOOP -->|all created| LOG["AuditLogger::log<br/>(action = TAG_GENERATED)"]
    LOG --> AUDIT[(audit_logs table)]
    LOG --> RESP["201: created asset list"] --> AT
```

Note: the batch is **not** re-checked for the next available number inside
the transaction — the client is expected to have called
`GET /tags/next-number` first. A collision (two Asset Team members
generating tags for the same prefix at the same instant) fails the whole
batch via the `tracking_number` unique constraint rather than silently
drifting numbers, which is a deliberate trade-off, not an oversight.

## 3. Asset assignment

Traced from `App\Http\Controllers\AssetDirectoryController::assign()`
→ `App\Services\AssetService::assign()`.

```mermaid
flowchart TD
    AT([Asset Team user]) -->|"asset_name, category,<br/>department, employee, quantity"| AAR["AssignAssetsRequest<br/>validates shape"]
    AAR --> TXN["DB::transaction starts"]
    TXN --> QUERY["Asset::where(asset_name, category)<br/>where(assigned_to IS NULL OR '')<br/>lockForUpdate()->take(quantity)->get()"]
    QUERY --> ASSETS[(assets table)]
    QUERY --> CHECK{"count(candidates) >=<br/>quantity requested?"}
    CHECK -- no --> FAIL["throw RuntimeException"] --> ERR422["422: 'No unassigned<br/>units available'"] --> AT
    CHECK -- yes --> UPDATE["foreach candidate:<br/>location = department<br/>assigned_to = employee<br/>status = Active"]
    UPDATE --> ASSETS
    UPDATE --> LOG["AuditLogger::log<br/>(action = ASSIGNED)"]
    LOG --> AUDIT[(audit_logs table)]
    LOG --> RESP["200: 'Successfully<br/>assigned N unit(s)'"] --> AT
```

`lockForUpdate()` is a no-op on SQLite (no row-level locking support), but
the whole read-then-update sequence still runs inside one
`DB::transaction()`, which is the actual correctness fix versus the prior
closure-based implementation that had no transaction boundary at all and
could double-assign the same units under concurrent requests.

## 4. Damage report submission

Traced from `App\Http\Controllers\DamageReportController::store()`
→ `App\Services\DamageReportService::submit()`.

```mermaid
flowchart TD
    ANY([Staff or Asset Team user]) -->|"tracking_number, asset_name,<br/>issue_type, description, image (file, optional)"| SDR["StoreDamageReportRequest<br/>validates shape + image is a real image, max 5MB"]
    SDR --> TXN["DB::transaction starts"]
    TXN --> PHOTO{"image file<br/>present?"}
    PHOTO -- yes --> STORE["store on 'public' disk,<br/>path damage_photos/..."]
    STORE --> DISK[("storage/app/public/<br/>damage_photos")]
    PHOTO -- no --> SKIP["image_path = null"]
    STORE --> CREATE
    SKIP --> CREATE["DamageReport::create<br/>(status = Pending)"]
    CREATE --> REPORTS[(damage_reports table)]
    CREATE --> COND["Asset::where(tracking_number)<br/>update(condition = Damaged)"]
    COND --> ASSETS[(assets table)]
    COND --> LOG["AuditLogger::log<br/>(action = REPORTED)"]
    LOG --> AUDIT[(audit_logs table)]
    LOG --> RESP["redirect back with<br/>'submitted successfully'"] --> ANY
```

This is the flow that was completely broken before the 2026-09-15
remediation: the previous implementation wrote to a table
(`activity_logs`) and a column (`image`) that never existed in the schema,
so every submission threw inside the transaction and rolled back silently
from the user's point of view. The current flow is covered end-to-end by
`tests/Feature/DamageReportTest::test_staff_can_submit_a_damage_report_with_a_photo`.
