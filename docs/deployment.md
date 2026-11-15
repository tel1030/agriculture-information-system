# Deployment and Database Setup

## Deploy to Render

This repository includes a Docker deployment for a Render Web Service. The container serves
only `public/`; application code, SQL files, scripts, and logs are not in the web document root.

1. Push the repository to GitHub. Do not commit `.env`, database passwords, certificates, or
   bootstrap Administrator credentials.
2. In Render, choose **New → Blueprint** and select the repository. Render will read
   `render.yaml` and build the `Dockerfile`.
3. After the service is created, set these secret environment variables in Render:
   `APP_URL` (the final `https://...onrender.com` URL), `SUPABASE_DB_HOST`,
   `SUPABASE_DB_USER`, and `SUPABASE_DB_PASSWORD`.
   In **Environment → Secret Files**, add a file named `prod-ca-2021.crt` using the
   contents of your trusted Supabase root certificate.
4. Apply `database/migrations/001_initial_schema.sql` in Supabase before opening the app.
   Use the Supabase pooler host and a restricted database user for the runtime connection.
5. Deploy and confirm Render's health check reports `/health.php` as healthy.
6. Create the first Administrator from a one-off Render shell or an approved local terminal
   using `AIS_BOOTSTRAP_ADMIN_EMAIL` and `AIS_BOOTSTRAP_ADMIN_PASSWORD`, then remove those
   variables immediately. The setup command is `php scripts/setup-admin.php`.

The Render configuration uses `SUPABASE_DB_SSLMODE=verify-full` and reads the trusted root
certificate from `/etc/secrets/prod-ca-2021.crt`, where Render mounts secret files at runtime.
The Windows certificate path in your local `.env` is for local development only; never use
that path as a Render environment value. Use the Supabase pooler endpoint recommended for the
selected project and keep the database user least-privileged.

Render's free service can sleep after inactivity and its local filesystem is ephemeral.
The application therefore writes production errors to stderr for Render logs and stores no
database data locally. Supabase remains the persistent data store.

## 1. Create a Supabase development project

1. Create a separate development project so the initial migration can be applied and tested without touching production data.
2. Run `database/migrations/001_initial_schema.sql` in the Supabase SQL editor as a project administrator.
3. Confirm there are exactly 13 AIS tables in the `public` schema and inspect foreign keys, check constraints, triggers, and the seeded remark IDs 1-5.
4. The migration revokes the Supabase `anon` and `authenticated` roles from the AIS tables and identity sequences. It intentionally does not enable RLS because the PHP application uses its own server-side session roles; adding RLS without a trusted per-request database identity would break or weaken that model.
5. Create a restricted PostgreSQL login for the PHP runtime. Keep the owner/migration identity separate. Grant only the table and sequence permissions the PHP routes require. Test the grants by connecting as the runtime identity.
6. Keep Data API exposure disabled for these tables unless explicit RLS policies are designed and tested. The application uses PHP/PDO and never connects from browser JavaScript.
7. Optionally run `database/seeds/demo_data.sql` once to add synthetic records. Do not import the personal-looking example phone numbers from the assignment screenshots.

After replacing/creating the role name through a trusted Supabase admin session, the permission shape for this application's runtime is:

<!-- prettier-ignore -->
```sql
GRANT CONNECT ON DATABASE postgres TO ais_runtime;

GRANT USAGE ON SCHEMA public TO ais_runtime;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE
    public.farmers, public.farms, public.crops, public.plantings, public.harvests
TO ais_runtime;

GRANT SELECT, INSERT, UPDATE ON TABLE
    public.crop_options, public.soil_types, public.irrigation_types,
    public.planting_statuses, public.harvest_options, public.user_preferences
TO ais_runtime;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE public.app_users TO ais_runtime;

GRANT SELECT, INSERT ON TABLE public.audit_logs TO ais_runtime;

GRANT USAGE, SELECT ON SEQUENCE
    public.farmers_farmer_id_seq,
    public.farms_farm_id_seq,
    public.crops_crop_id_seq,
    public.plantings_planting_id_seq,
    public.harvests_harvest_id_seq,
    public.crop_options_option_id_seq,
    public.soil_types_soil_type_id_seq,
    public.irrigation_types_irrigation_type_id_seq,
    public.planting_statuses_status_id_seq,
    public.harvest_options_option_id_seq,
    public.app_users_user_id_seq,
    public.audit_logs_log_id_seq TO ais_runtime;
```

Use the actual runtime role name and database name from the project. Do not store the migration owner password in the web app.

## 2. Configure PHP

Copy `.env.example` to `.env` for local development, or set the same values in the hosting secret manager. Set the `SUPABASE_DB_*` values from the connection method selected in the Supabase dashboard. Use TLS and a trusted certificate for `verify-full`. An IPv4-only host may need the Supabase shared pooler in Session mode. Keep `.env`, certificate files, migration credentials, and logs outside `public/` and out of Git.

The PHP runtime needs PHP 8.2 or later and `pdo_pgsql`. Set:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_SESSION_SECURE=true`
- `APP_TRUSTED_PROXY=true` only when a trusted reverse proxy terminates TLS, strips user-supplied forwarded-protocol headers, and sets its own HTTPS marker; otherwise leave it false
- `APP_URL` to the HTTPS site URL
- `APP_TIMEZONE` to the deployment timezone

The reverse proxy must send traffic over HTTPS. Configure it to redirect HTTP to HTTPS, disable public PHP error display, and write logs to a private path.

## 3. Set up the initial Administrator

From a trusted terminal, set `AIS_BOOTSTRAP_ADMIN_EMAIL` and `AIS_BOOTSTRAP_ADMIN_PASSWORD` only for the setup process, then run `php scripts/setup-admin.php`. The command only accepts the teacher-provided Administrator email, hashes the password, refuses to overwrite an existing account, and requires a password change at first sign-in. Remove the temporary environment variables after success. Do not add the password to `.env.example`, SQL, documentation, source code, screenshots, or Git history.

## 4. Web server layout

Set the web document root to the project's `public/` directory. Requests to `app/`, `database/`, `scripts/`, `storage/`, `.env`, and the repository root must not be served as public files. Do not publish this application until HTTPS, environment configuration, database grants, backup policy, and the initial account have been checked.

For local development after setting `.env`, run from the project root:

```powershell
php -S 127.0.0.1:8000 -t public
```

Use HTTPS or localhost for PWA behavior. The service worker caches public static assets and the offline message; it does not cache authenticated PHP pages, forms, reports, or farmer data.

## 5. Before production or class demonstration

- Apply the migration and verify the exact table count in the intended database.
- Create the restricted runtime database identity and test SELECT/INSERT/UPDATE/DELETE needs.
- Create the first Administrator securely and immediately replace its temporary password.
- Sign in as both roles; verify forbidden pages and deletion attempts are denied server-side.
- Run the test plan in `docs/test-plan.md`, record actual results, and capture screenshots from the working system.
- Check dashboard totals and report totals against direct PostgreSQL queries.
- Verify responsive layouts, print/CSV, the light/dark Profile choice, and PWA offline fallback over a secure origin.
- Establish backup retention and perform a restore rehearsal in a non-production database.

This repository is prepared for deployment configuration; it is not a deployed system. A live Supabase project, hosting, HTTPS, least-privilege grants, and executed acceptance evidence are still required.
