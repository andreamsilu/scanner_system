# Event ticketing and admission system

Laravel 12 application for events, attendee registration, QR tickets, scanner assignments, admission history, CSV/XLSX imports, ticket exports and SMS notifications. The interface uses Blade, Livewire 4, Tailwind CSS 4 and the free Flux UI components; Vite builds the frontend assets.

## Interface

The shared workspace has a responsive sidebar, keyboard-accessible navigation, and a persistent light/dark appearance switch. Sign-in, administrator/scanner overviews, ticket management and the scanner use the new design. Older administration forms retain scoped Bootstrap styles during the gradual migration.

The shared layouts serve Inter font files and compatibility libraries from the app's own build. Modern pages omit jQuery, Bootstrap, DataTables and Toastr entirely. Older forms load local classic scripts in dependency order; the Vite static-copy plugin supplies the same paths in development and production. Deploy the complete `public/build` directory, including `legacy` and font assets. Some page-specific chart, date-picker and table-export extensions still use their existing CDN links.

Ticket management uses the `TicketBrowser` Livewire component for event-scoped name/phone/number search, type/status filters, numeric sorting and pagination. It shows partial admissions and keeps print, export, sharing, deactivation and invitation actions. Every Livewire request rechecks administrator access.

The scanner bundles its QR reader locally. Camera access starts only on request; manual entry remains available when a camera is unavailable. Results stay visible until the next result, and double-ticket choices retain single entry as the default focus.

Shared design tokens and reusable styles live in `resources/css/app.css` and `workspace.css`. `legacy.css` is prefixed to `.legacy-ui` at build time so Bootstrap cannot override Flux navigation. New screens should use Flux and Tailwind, and be excluded from the legacy wrapper in `layouts/workspace.blade.php`. Livewire supplies Alpine; do not load another Alpine instance. No Flux Pro license is required.

After updating, run `composer install`, `npm ci`, `npm run build` and `php artisan view:clear`. Serve the generated `public/build` assets and allow Laravel's Livewire and Flux asset/update routes through your web server. The modern UI requires browsers supported by Tailwind CSS 4 (Safari 16.4+, Chrome 111+, Firefox 128+). Production camera access requires HTTPS.

## Ticket numbering and entry rules

- Each event numbers its tickets **1, 2, 3, ...** independently. Deleting a ticket does not reuse its number. Internal database keys remain globally unique.
- QR codes use a separate random credential. A visible ticket number is meaningful only within its event.
- Single, regular and VIP tickets admit one person.
- A double ticket prompts staff to choose **Single entry** (one person) or **Double entry** (two people). Single entry is focused by default. Cancelling the prompt consumes nothing.
- After single entry on a double ticket, one admission remains and the ticket stays active. After both admissions are consumed, its status becomes used. Only single entry is available when one admission remains.
- Admission is checked and recorded in one database transaction. Confirmations include the previously observed entry count, so simultaneous confirmations cannot spend the remaining admission accidentally.
- Staff must be active and assigned to the selected event, or be an administrator. Cancelled registrations/tickets and closed events are rejected.
- Public ticket links display information; opening or previewing a link never admits anyone. Admission requires a signed-in staff POST request.

## Requirements

- PHP 8.2+ and Composer; PDO MySQL, GD, mbstring, XML, fileinfo, OpenSSL and ZIP extensions.
- MySQL 8+ for deployment. PDO SQLite is required for the default isolated test suite.
- Node.js 22.12+ and npm for frontend builds.
- A queue worker, persistent storage, HTTPS and configured mail/SMS credentials for deployment.

QR codes are generated locally. SVG is used for stored QR images; JPEG tickets use GD to render QR modules without sending credentials to an external image service.

## Development setup

Use Node and npm from the same operating system where you run Vite. In WSL, activate your Linux Node installation (for example, `nvm use 24`) and run `npm ci` inside `/mnt/d/msilu/scanner_system` before starting Vite. Windows and WSL cannot share an installed `node_modules` directory reliably: switching environments requires another `npm ci`, which replaces generated launchers and platform-specific binaries while keeping `package-lock.json` unchanged. If Vite reports `exec: node.exe: Permission denied` in WSL, run `npm ci` with Linux npm; changing file permissions is not the fix.

If Windows resets the connection to `localhost:8000` while a request inside WSL succeeds, check `netsh interface portproxy show all` in Windows. A rule forwarding port 8000 to WSL's network address cannot reach PHP when it listens only on WSL's loopback address. Use an unused local port, for example `php artisan serve --host=127.0.0.1 --port=8001 --no-reload`, and open `http://127.0.0.1:8001/login`. This avoids changing existing Windows forwarding rules. Nginx deployments do not need `artisan serve`.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Configure APP_URL, the database, mail and SMS settings in `.env`, then run:

```sh
php artisan migrate
php artisan storage:link
php artisan app:create-admin admin@example.com --name="Administrator"
npm run build
php artisan serve
```

The administrator command asks for a private password and creates the admin/scanner roles. It refuses to overwrite an existing account. No shared initial password is required. Demo seeders are for local/testing environments and refuse to run in production.

Run the worker in a separate terminal:

```sh
php artisan queue:work --tries=3 --timeout=300
```

Use QUEUE_CONNECTION=database. Its retry interval defaults to 360 seconds, longer than the import job timeout. Restart workers after deploying code changes. Bulk uploads are durable jobs, CSV rows are streamed, template heading annotations are accepted, and retries skip already imported rows from that upload. Uploads are limited to 10 MB and 10,000 rows; invalid rows are reported in the upload status page.

Accounts are administered through user management. Public registration, password recovery and standalone password confirmation endpoints are currently closed. Signed-in users can change their own password and verify their email. Configure outbound mail before using email verification.

## Upgrading an existing installation

Back up the database and storage, and rehearse the upgrade on a restored copy first. Stop admission traffic and pause/drain workers during the migration.

The 2026-10-25 migration renumbers existing tickets by database ID within each event, retains their former number in `legacy_ticket_number`, and preserves existing QR credentials. Existing used double tickets remain fully consumed; they do not gain another admission. Tell staff to use the new event ticket numbers for manual entry; old printed QR codes still work.

The migration also adds entry counters, admission quantities, import status fields and foreign-key restrictions that prevent deleting admission history through cascading ticket/user deletion. Baseline migrations no longer erase existing event data. A database whose tables exist without matching migration history must be reconciled before migration rather than reset.

Typical deployment sequence, after taking and verifying a backup:

```sh
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Set APP_ENV=production and APP_DEBUG=false. Use HTTPS, supervise the worker, monitor failed jobs and retain backups. Use separate migration credentials and restrict application access to the admission log table to SELECT/INSERT where supported. Application admission logs reject updates/deletes; scanned events must be retained rather than deleted.

For slow pages, measure Laravel separately from browser downloads: `curl -sS -o /dev/null -w 'HTTP %{http_code}; first byte %{time_starttransfer}s; total %{time_total}s\n' http://127.0.0.1:8088/login` (adjust the port). A fast first byte with a slow browser load calls for checking asset requests in the browser Network panel. Build assets with `npm ci --include=dev` and `npm run build`; production should not contain `public/hot` or depend on `npm run dev`. After deploying, run `php artisan optimize` as the PHP-FPM user so configuration, routes and views are cached without creating root-owned runtime files. Nginx should serve `/build/` files directly, with compression enabled for CSS/JavaScript and long cache lifetimes restricted to fingerprinted assets. If PHP itself is slow, inspect PHP-FPM/OPcache, database timings and server CPU/memory before changing application behavior.

Renumbering/admission tracking intentionally refuses automatic rollback. Restore the verified pre-upgrade backup if the data migration must be reversed; do not run migration resets on a populated deployment.

## Validation

```sh
composer validate --strict
composer audit
composer test
npm test
npm run build
```

The default PHP suite uses an in-memory SQLite database and a test-only encryption key; it does not use deployment data. It covers entry limits, double-entry choices, stale confirmations, permissions, account status, public read-only links, imports, exports, UI rendering, Livewire filters/access revocation and preservation of existing records during upgrade. Frontend tests cover the entry dialog, cancellation and repeated camera frames.

Real concurrency tests require a disposable MySQL database whose name ends in `_test`. Configure its DB_* environment variables and run:

```sh
php vendor/bin/phpunit --testsuite=Integration
```

Those tests recreate that test database and launch independent PHP workers to race ticket issuance and admission. They skip on SQLite. The GitHub Actions workflow runs PHP 8.2/8.3 tests, frontend checks, dependency audits and the MySQL concurrency suite.
