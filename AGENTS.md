# AGENTS.md

## Overview
- Root is not a monorepo: Docker stack lives at the root, the Laravel 13 app lives in `src/`.
- Host has **no PHP, Composer, Node, or npm** installed. Run all app/tooling commands through `docker-compose`.
- Planning docs: `docs/README.md`. Phases 0–9 are implemented for the current MVP scope; CI remote verification and release tagging remain explicit release steps.

## Quick start
```sh
docker-compose up -d --build
```
- App: http://localhost:8080
- Vite: http://localhost:5173
- Mailpit UI: http://localhost:8025 (SMTP `localhost:1025`)
- MySQL: `127.0.0.1:3306` (`laravel` / `laravel` / `laravel`)
- Redis: `127.0.0.1:6379`
- Services: `app` (PHP-FPM), `worker` (queue), `scheduler`, `web` (Nginx), `db` (MySQL 8.4), `redis` (7), `mailpit`, `vite` (Node 22)
- Entrypoint `docker/php/entrypoint.sh` copies `.env` if missing, installs `vendor/` on first boot, generates `APP_KEY`, waits for MySQL, and runs `php artisan migrate --force` only when `RUN_MIGRATIONS=1` (the `app` container). Workers and scheduler share the same image but skip migrations.

## Commands (use containers)
```sh
docker-compose exec app php artisan migrate
docker-compose exec app php artisan test
docker-compose exec app php artisan test --filter=PagesTest
docker-compose exec app composer require <package>
docker-compose exec app ./vendor/bin/pint
docker-compose exec app ./vendor/bin/pint --test
docker-compose exec app ./vendor/bin/phpstan analyse
docker-compose exec app composer run quality
docker-compose exec app php artisan scramble:export
docker-compose exec vite npm run build
```
- Ticket numbers are `TKT-YYYYMMDD-NNNN`, sequential per organization per day; generation locks the organization row, so keep ticket creation inside a DB transaction.
- Portal ticket pages live under `/dashboard`, `/tickets`, `/tickets/create`, `/tickets/{id}`; the Phase 2 portal uses plain Blade forms.
- Agent/admin console lives under `/admin` (`/admin/tickets`, `/admin/settings/{categories,tags,sla,teams}`, `/admin/members`, `/admin/audit`). Only staff (`owner|admin|agent`) may enter; master data, members, and audit are `owner|admin` only.
- Agent queue/detail are Livewire components (`support.ticket-queue`, `support.ticket-detail`) registered in `src/app/Modules/Support/Providers/SupportServiceProvider.php`.
- Livewire update requests skip route middleware, so components persist `#[Locked] organizationId`/`ticketId` and re-authorize in `hydrate()`; never call `tenant()` outside `mount()`.
- Audit events are written via `App\Modules\Support\Services\AuditLogger` (ticket services plus admin controllers) into `audit_logs`.
- API ticket endpoints live under `/api/v1/tickets*` and require `Accept: application/json` plus `X-Organization: <organization_id>` for tenant scoping.
- Tests use SQLite `:memory:` from `src/phpunit.xml`; they do not touch MySQL, Redis, or Mailpit.
- Login is rate limited to 5/min per email+IP, so repeated API smoke logins return `429 Too Many Attempts` (non-JSON body). Run `docker-compose exec app php artisan cache:clear` between smoke rounds or reuse one token.
- `docker-compose` exports `.env` values into the container, so `src/tests/bootstrap.php` forces `APP_ENV=testing`, SQLite, array cache/session, and sync queue before Laravel boots. Keep it in sync with `phpunit.xml`.
- Feature tests rendering pages with `@vite` must call `$this->withoutVite()`.
- Livewire tests use the `actingAsTenant()` helper in `src/tests/TestCase.php` so `tenant()` is bound before `mount()`; use `addTenantMember()` to attach extra users to an existing organization.
- After changing `package.json`: `docker-compose run --rm vite npm ci`, then `docker-compose restart vite`.
- After changing `.env`: `docker-compose exec app php artisan config:clear`.
- `docker-compose exec app php artisan migrate:fresh --seed` is destructive.
- Send a test email: `docker-compose exec app php artisan tinker --execute="Mail::raw('test', fn (\$m) => \$m->to('qa@supportflow.test')->subject('test'));"` then check Mailpit.

## Gotchas
- This machine has standalone `docker-compose`; `docker compose` is unavailable.
- `.env` / `.env.example` are configured for MySQL host `db`, Redis host `redis`, and Mailpit host `mailpit`.
- `src/vendor` is a host bind mount; `src/node_modules` is a named Docker volume.
- Nginx resolves `app` through Docker DNS (`resolver 127.0.0.11`), so pages stay up when the `app` container is recreated.
- API docs (Scramble) live at `/docs/api` and `/docs/api.json`.
- Laravel `^13.17`, PHP `^8.3` (container uses 8.4), Node 22 in the `vite` container.
- Do not run `php`, `composer`, `npm`, or `node` directly on the host.

## Layout
- Root: `docker-compose.yml`, `docker/`, `docs/`, `.github/workflows/ci.yml`, `AGENTS.md`, `README.md`
- `src/`: Laravel app (`artisan`, `routes/`, `app/`, `tests/`, `resources/`, `phpstan.neon`)
