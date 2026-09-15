# Routes Reference

Generated from `php artisan route:list` plus the middleware and
FormRequest bound to each controller action in `routes/web.php` /
`app/Http/Controllers`. 28 routes total; the last 3 rows are Laravel
framework routes, not application routes (health check + local disk
serving for `Storage::disk('public')`, used by uploaded damage-report
photos).

| Method | URI | Name | Middleware | Controller action | Validates via |
|---|---|---|---|---|---|
| GET | `/` | — | none | closure → redirect to `login` | — |
| GET | `/login` | `login` | none | `AuthController@showLogin` | — |
| POST | `/login` | `login.post` | none | `AuthController@login` | `LoginRequest` |
| GET | `/register` | `register` | none | `AuthController@showRegister` | — |
| POST | `/register` | `register.post` | none | `AuthController@register` | `RegisterRequest` |
| POST | `/logout` | `logout` | `auth` | `AuthController@logout` | — |
| GET | `/staff_dashboard` | `staff_dashboard` | `auth`, `role:staff` | `DashboardController@staffDashboard` | — |
| POST | `/damage-queue/report` | `report_damage.store` | `auth` | `DamageReportController@store` | `StoreDamageReportRequest` |
| GET | `/api/assets/tracking/{trackingNumber}` | `assets.get_by_tracking` | `auth` | `Api\AssetApiController@byTracking` | — |
| GET | `/dashboard` | `asset_dashboard` | `auth`, `role:asset_team` | `DashboardController@assetDashboard` | — |
| GET | `/assets` | `asset_directory` | `auth`, `role:asset_team` | `AssetDirectoryController@index` | — |
| POST | `/assets/assign` | `assets.assign` | `auth`, `role:asset_team` | `AssetDirectoryController@assign` | `AssignAssetsRequest` |
| POST | `/categories/store` | `categories.store` | `auth`, `role:asset_team` | `CategoryController@store` | `StoreCategoryRequest` |
| PUT | `/categories/update` | `categories.update` | `auth`, `role:asset_team` | `CategoryController@update` | `UpdateCategoryRequest` |
| DELETE | `/categories/delete` | `categories.destroy` | `auth`, `role:asset_team` | `CategoryController@destroy` | `DestroyCategoryRequest` |
| GET | `/analysis/asset` | `asset_analysis` | `auth`, `role:asset_team` | `AssetAnalysisController@index` | — |
| GET | `/analysis/location` | `location_analysis` | `auth`, `role:asset_team` | `LocationAnalysisController@index` | — |
| GET | `/logs` | `log_history` | `auth`, `role:asset_team` | `LogHistoryController@index` | — |
| GET | `/damage-queue` | `damage_queue` | `auth`, `role:asset_team` | `DamageReportController@index` | — |
| PATCH | `/damage-queue/{report}/status` | `damage_queue.update_status` | `auth`, `role:asset_team` | `DamageReportController@updateStatus` | `UpdateDamageReportStatusRequest` |
| GET | `/tags/next-number` | `tags.next_number` | `auth`, `role:asset_team` | `TagGeneratorController@getNextNumber` | — |
| POST | `/tags/bulk-generate` | `generate_bulk_tags` | `auth`, `role:asset_team` | `TagGeneratorController@storeBulkTags` | `GenerateTagsRequest` |
| GET | `/api/assets/by-type` | `assets.by_type` | `auth`, `role:asset_team` | `Api\AssetApiController@byType` | — |
| PUT | `/api/assets/{asset}` | `assets.update` | `auth`, `role:asset_team` | `Api\AssetApiController@update` | `UpdateAssetRequest` |
| DELETE | `/api/assets/{asset}` | `assets.destroy` | `auth`, `role:asset_team` | `Api\AssetApiController@destroy` | — |
| GET/HEAD | `/up` | — | none | framework health check | — |
| GET/HEAD | `/storage/{path}` | `storage.local` | framework | serves the `public` disk | — |
| PUT | `/storage/{path}` | `storage.local.upload` | framework | uploads to the `public` disk | — |

## What is intentionally absent

No route exists for: password reset/forgot-password, email verification,
user profile editing, admin user management, or a dedicated status-history
view for a damage report beyond the single Pending→Resolved transition.
These are not hidden or planned — `routes/web.php` simply has no entries
for them.
