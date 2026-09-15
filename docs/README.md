# Inventory System — Documentation

Documentation set for the Olympia Education asset-tracking system, generated
against the codebase as of branch `refactor/architecture-standard`, commit
`59c0ddf`. Every diagram and table here is derived directly from the source
(routes, migrations, models, controllers, services) — nothing is assumed or
invented. Where the UI still contains cosmetic/disconnected elements (e.g.
static demo filter options), that is called out explicitly rather than
documented as if it were live behaviour.

## Contents

1. [Architecture](./architecture.md) — layered structure, request lifecycle,
   directory map.
2. [Use Case Diagram](./use-case-diagram.md) — what each role (Staff, Asset
   Team) can actually do, grounded in the route/middleware table.
3. [Data Flow Diagrams](./data-flow-diagrams.md) — the four core flows:
   authentication, tag generation, asset assignment, damage reporting.
4. [Entity-Relationship Diagram](./erd.md) — schema as it exists in
   `database/database.sqlite`, with every column, type, and constraint.
5. [Routes Reference](./routes.md) — full route table with HTTP method,
   middleware, controller action, and FormRequest.
6. [Known Limitations](./known-limitations.md) — things that are true of the
   current build and worth knowing before extending it.

## How this was verified

- Schema: `sqlite3 database/database.sqlite ".schema <table>"` on the actual
  file, not the migration source (confirms migrations ran as written).
- Routes: `php artisan route:list`.
- Roles/middleware: read directly from `routes/web.php` and
  `App\Http\Middleware\EnsureUserHasRole`.
- Models/relationships: read directly from `app/Models/*.php`.
- Test coverage claims: `php artisan test` (35 passed, 0 failed at time of
  writing).
