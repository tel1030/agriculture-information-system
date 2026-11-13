# Agriculture Information System

A server-rendered agriculture records application for the AP3 midterm project. It uses PHP 8.2+, PDO PostgreSQL, Supabase PostgreSQL, HTML, CSS, and small vanilla JavaScript enhancements. The web server must expose only `public/`; application code, migrations, and local secrets stay outside the document root.

## Implemented areas

- Administrator and Staff sign-in, role checks, password hashing, account lockout, CSRF protection, secure session handling, and forced first password change.
- CRUD for Farmers, Farms, Crops, Plantings, and Harvests, with joined labels, controlled dropdowns, server validation, bounded pagination, search, filters, sorting, dependency-aware deletes, and audit records.
- Dynamic dashboard totals, status counts, recent activity, recorded harvest kilograms, estimated value, and six report views with date filters and CSV export.
- Administrator user and lookup management, activity history, personal theme preference, responsive sidebar, light/dark themes, and a safe static-only PWA shell.
- A PostgreSQL migration with exactly 13 meaningful tables.

## Requirements

- PHP 8.2 or newer with `pdo_pgsql` enabled.
- A Supabase PostgreSQL database (or PostgreSQL 14+ for local development).
- HTTPS in production. A local PHP server can be used for development.
- Render deployment is configured with `Dockerfile` and `render.yaml`.

## Local configuration

1. Copy `.env.example` to `.env` and enter the Supabase database host, restricted runtime user, and password from your project. Keep `.env` out of source control.
2. For local use, set `APP_ENV=local`, `APP_DEBUG=false`, and `APP_SESSION_SECURE=false`. Production must use HTTPS and secure cookies.
3. Apply `database/migrations/001_initial_schema.sql` once to a new development Supabase project using the SQL editor. This migration seeds controlled dropdown values, including harvest remark IDs 1-5.
4. Optionally apply `database/seeds/demo_data.sql` once to add clearly synthetic, non-personal records for a live classroom demonstration.
5. Point the local web server document root at this project's `public/` folder. For a quick local page server, run `php -S 127.0.0.1:8000 -t public` from the project directory.
6. Create the first Administrator from a terminal after the database is configured. Supply the teacher-requested initial account through process environment variables; the password is never stored as plain text or written into this repository.

PowerShell example for the one-time setup process:

```powershell
$env:AIS_BOOTSTRAP_ADMIN_EMAIL = 'admin@gmail.com'
$env:AIS_BOOTSTRAP_ADMIN_PASSWORD = Read-Host 'Initial admin password' -AsSecureString | ConvertFrom-SecureString -AsPlainText
php scripts/setup-admin.php
Remove-Item Env:AIS_BOOTSTRAP_ADMIN_EMAIL
Remove-Item Env:AIS_BOOTSTRAP_ADMIN_PASSWORD
```

The app requires the Administrator to replace the initial password at first sign-in. The setup command refuses to overwrite an existing account. Use a temporary shell session and clear both variables after setup.

For the Supabase runtime connection, use a dedicated least-privilege database login and TLS. Direct database access requires suitable network support; an IPv4-only host may need Supabase's session pooler. Set `SUPABASE_DB_SSLROOTCERT` to a trusted root certificate path when using `verify-full`. The migration should be run by a project administrator, not by the web application's runtime login. Adapt Supabase grants to your project and verify them with the runtime account.

## Render deployment

Use Render **New → Blueprint** with this repository. The included `render.yaml` builds the
PHP/Apache container and configures `/health.php` as the health check. Set `APP_URL`,
`SUPABASE_DB_HOST`, `SUPABASE_DB_USER`, and `SUPABASE_DB_PASSWORD` as Render environment
secrets. Full deployment steps and the required Supabase preparation are in
[docs/deployment.md](docs/deployment.md).

## Project structure

```text
app/                    Shared PHP bootstrap, module definitions, and views
database/migrations/    Versioned PostgreSQL schema
public/                 Web document root, static CSS/JS, manifest, service worker
scripts/                One-time CLI Administrator setup
storage/logs/           Private server logs (outside public/)
docs/                   Requirements comparison, schema, normalization, guide, test plan
.env.example            Safe configuration template
```

In VS Code, open the named pages in `public/`: `index.php` is the overview; `farmers.php`, `farms.php`, `crops.php`, `plantings.php`, and `harvests.php` open each record module; `login.php`, `logout.php`, `profile.php`, `reports.php`, `users.php`, and `activity.php` open the other main sections. Each page is a small entry point that uses the shared routing and security logic. The one-time `setup-admin.php` command is under `scripts/` and is CLI-only.

## Database design and interpretation notes

The instructor's document requires exactly 13 tables. The SDD proposed 15. The implementation keeps all five required business tables, groups the crop category/season values in one typed reference table, groups harvest grade/remark values in one typed reference table, stores the role as a constrained app-user field, and adds a profile-preference table for the requested persistent theme choice. This results in exactly 13 tables with concrete purposes. Details, relationships, normalization, and data dictionary are in [docs/system-design.md](docs/system-design.md).

The instructor explicitly fixes harvest remark IDs 1-5. They are seeded as:

|  ID | Description        |
| --: | ------------------ |
|   1 | Good Harvest       |
|   2 | Acceptable Quality |
|   3 | Low Yield          |
|   4 | Poor Quality       |
|   5 | Damaged Harvest    |

The source assignment does not identify units for `area_planted` or `seed_quantity`, nor currency for the selling price. The app preserves those source field meanings, shows the unit assumption as a note, and labels the monetary estimate as PHP based on the SDD assumption. Confirm those assumptions with the instructor before a formal presentation.

## Security and operations

- All state changes use POST plus a session CSRF token. GET routes are read-only.
- SQL values use PDO parameters. Sort keys and table/field writes come from application allow-lists.
- Passwords use `password_hash()` and `password_verify()`; hashes are never included in views, reports, or audit data.
- Role checks run on the server for every administrator-only page and delete operation.
- Deleting a farmer, farm, crop, or planting explicitly removes dependent planting and harvest records in the same transaction, with an audit entry for each deleted record and a confirmation that explains the impact. Lookup values remain protected and are deactivated instead.
- Authenticated pages are `no-store`; the service worker caches only the public offline page and static assets.
- Configure production HTTPS, secure cookies, private logs, backups, and least-privilege database grants before deployment.

## Verification status

PHP 8.3 with PDO PostgreSQL is available in the current workspace. Database-dependent behavior cannot be demonstrated until Supabase connection variables and a project are configured. The instructor requires working-system screenshots and executed test results; do not treat the supplied design screenshots or this source code as evidence of a live deployment. See [docs/test-plan.md](docs/test-plan.md) for the verification record and outstanding checks.
