# Inventory System

Office asset-tracking system for Olympia Education: tag generation, asset
assignment, damage reporting, and asset/location analysis dashboards.

## Stack

- Laravel 12 (PHP 8.2), SQLite
- Vite 7 + Tailwind CSS v4
- Pest/PHPUnit for tests, Pint for formatting

## Roles

- **Staff** — reports damaged assets, views their own report history and
  assigned assets.
- **Asset Team** — everything Staff can do, plus asset directory
  management, tag generation, damage-queue resolution, categories, and
  the analysis dashboards.

Role separation is enforced by the `role` middleware
(`App\Http\Middleware\EnsureUserHasRole`) on every management route.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
```

## Seeded accounts

| Role        | Email                | Password    |
|-------------|-----------------------|-------------|
| Staff       | staff@company.com     | password123 |
| Asset Team  | admin@company.com     | password123 |

## Architecture

- `routes/web.php` — route declarations only.
- `app/Http/Controllers` — thin controllers; delegate to services.
- `app/Services` — business logic (assignment, tag generation, damage
  reports, audit logging, analytics).
- `app/Http/Requests` — validation per write endpoint.
- `app/Enums` — the fixed vocabulary for roles, asset status/condition,
  damage-report status, and audit actions.

## Testing

```bash
php artisan test
```
