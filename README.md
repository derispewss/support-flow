# SupportFlow

---

## Role‑Based Journeys

### Super‑Admin
- **Goal:** Manage the entire SaaS platform (all tenants, global settings, audit logs).
- **Key UI:** `layouts.superadmin` with a full‑height shadcn/ui sidebar.
- **Typical Flow:**
  1. Log in as `superadmin@supportflow.test`.
  2. Dashboard shows overall statistics.
  3. **Organizations** → *Create* a new workspace (owner will later own it).
  4. **Users** → manage global users, assign the `superadmin` role.
  5. **Audit Logs** → view every event across tenants.
  6. **Global Settings** → toggle feature flags, set plan limits.
- **Permissions:** `Gate::allows('superadmin')` on every route prefixed with `superadmin.*`.

### Owner (Workspace Administrator)
- **Goal:** Own a workspace, configure its master data, invite members, and monitor tickets.
- **Key UI:** `layouts.admin` (shadcn/ui Sidebar) with additional menus that only owners see (Categories, SLA, Members).
- **Typical Flow:**
  1. Register / receive an invitation → **On‑boarding wizard** asks for a workspace name.
  2. `OrganizationProvisioner` creates the organization and seeds default **categories**, a **knowledge‑base category**, and two **SLA policies**.
  3. After login you land on the **Owner Dashboard** (metrics for the current tenant).
  4. **Invite** → send email invitations to Agents or Customers (role is encoded in the signed invitation link).
  5. **Configure Master Data** → create / edit / delete Categories, Tags, SLA policies, Teams.
  6. **Monitor** → view ticket metrics, SLA risk, recent activity.
- **Permissions:** `Gate::allows('owner')` – all routes inside the tenant context.

### Agent (Support Staff)
- **Goal:** Handle tickets assigned to the workspace.
- **Key UI:** Same `layouts.admin` but the sidebar hides master‑data links; only **Ticket Queue**, **My Tickets**, and **Reports** are shown.
- **Typical Flow:**
  1. Log in (email `agent@supportflow.test`).
  2. Open **Ticket Queue** → filter by status, priority, category, tags.
  3. **Take a ticket** (Assign to self or pick from the dropdown).
  4. **Classify** the ticket – set **Category** (must be active) and **Priority**.
  5. **Reply** – add a public message, attach files, or add an internal note (visible only to staff).
  6. **Change status** – open, pending, resolved, closed – SLA timers are updated automatically.
  7. **Tag / Watch** – add/remove tags, add watchers for future updates.
- **Permissions:** `Gate::allows('agent')` (or `staff`). Agents cannot create/edit master data.

### Customer (End‑User)
- **Goal:** Submit a support request and track its progress.
- **Key UI:** `layouts.portal` – a clean portal layout with a vertical shadcn/ui sidebar (Home, My Tickets, Knowledge Base, Settings).
- **Typical Flow:**
  1. Register or receive an invitation link that **joins** a workspace (the link contains a signed URL with the organization slug).
  2. After acceptance the user is automatically attached to the organization as a **customer**.
  3. **Create Ticket** → fill subject, description, select a **Category** (populated from the organization’s active categories) and a **Priority**.
  4. **My Tickets** → list only tickets where `requester_id` = current user.
  5. **Ticket Detail** – view conversation timeline, add replies, upload attachments. No internal‑note option is shown.
  6. Receive email / in‑app notifications when an Agent replies or the ticket status changes.
- **Permissions:** `Gate::allows('customer')`. Customers never see the admin sidebar or any master‑data pages.

---

## Development & Usage Commands

```sh
# Start the whole stack (Docker Compose)
 docker-compose up -d --build

# Reset the database, run fresh migrations and seed default data (categories, KB, SLA)
 docker-compose exec -T app php artisan migrate:fresh --seed

# Clear all caches and compiled files (important after any view or config change)
 docker-compose exec -T app php artisan cache:clear
 docker-compose exec -T app php artisan config:clear
 docker-compose exec -T app php artisan route:clear
 docker-compose exec -T app php artisan view:clear
 docker-compose exec -T app php artisan optimize:clear

# (Optional) Regenerate compiled view files for production
 docker-compose exec -T app php artisan view:cache

# Run the test suite – should pass 150+ tests
 docker-compose exec -T app php artisan test

# Run quality tools (Pint, PHPStan)
 docker-compose exec -T app composer run quality

# Build front‑end assets with Vite
 docker-compose exec -T vite npm run build
```

---

## Quick Demo Accounts (already seeded)

| Role | Name | Email | Password |
|------|------|-------|----------|
| Super‑admin | Superadmin SupportFlow | `superadmin@supportflow.test` | `Password123` |
| Owner | Rina Wulandari | `owner@supportflow.test` | `Password123` |
| Agent | Dimas Prakoso | `agent@supportflow.test` | `Password123` |
| Customer | Budi Santoso | `customer@supportflow.test` | `Password123` |

---

*All URLs are relative to `http://localhost:8080`.*

Multi-tenant customer support & knowledge base platform. Laravel 13 + Livewire 4 + Tailwind CSS 4, running on Docker Compose (Nginx + PHP-FPM + MySQL + Redis + Mailpit + Vite).

**Status:** Phase 0–8 selesai; Phase 9 selesai untuk dokumentasi, seeder, dan smoke-test release.

## Documentation

- [docs/README.md](docs/README.md) — indeks dokumentasi
- [docs/PRD.md](docs/PRD.md) — product requirements
- [docs/SYSTEM-DESIGN.md](docs/SYSTEM-DESIGN.md) — desain sistem
- [docs/12-ui-tech-stack.md](docs/12-ui-tech-stack.md) — breakdown UI stack
- [docs/10-roadmap.md](docs/10-roadmap.md) — tahapan implementasi

## Requirements

- Docker with Compose (`docker-compose`)

## Start

```sh
docker-compose up -d --build
```

- App: http://localhost:8080
- API docs (Scramble): http://localhost:8080/docs/api
- Vite dev server: http://localhost:5173
- Mailpit: http://localhost:8025 (SMTP `localhost:1025`)
- MySQL: `127.0.0.1:3306` (database/user/password: `laravel`)
- Redis: `127.0.0.1:6379`

The `app` entrypoint creates `.env` if needed, installs Composer dependencies on first boot, generates `APP_KEY`, waits for MySQL, and runs migrations. `worker` and `scheduler` reuse the same image and skip migrations. Nginx resolves the `app` container through Docker DNS, so it survives container recreation.

## Production deployment

The CI workflow publishes the application and Nginx images to GHCR after the quality checks pass. MySQL, Redis, and Mailpit use their upstream images and can also be replaced through `DB_IMAGE`, `REDIS_IMAGE`, and `MAIL_IMAGE`. On the server, copy `.env.production.example` to `.env`, set the production values (including a persistent `APP_KEY`), log in to GHCR if the packages are private, then run:

```sh
docker login ghcr.io
docker-compose -f docker-compose.production.yml pull
docker-compose -f docker-compose.production.yml up -d
```

The production Compose file has no build steps or source-code bind mounts. Updating the release only requires changing `APP_IMAGE` and `WEB_IMAGE` in `.env` when using a non-`latest` tag, then running `pull` and `up -d` again.

## Demo accounts

All demo accounts use the password `Password123` and belong to **PT Contoh Nusantara**.

| Role | Name | Email | Access |
|---|---|---|---|
| Superadmin | Superadmin SupportFlow | `superadmin@supportflow.test` | Full admin console access |
| Owner | Rina Wulandari | `owner@supportflow.test` | Organization and member management |
| Agent | Dimas Prakoso | `agent@supportflow.test` | Ticket queue and ticket handling |
| Customer | Budi Santoso | `customer@supportflow.test` | Customer portal and own tickets |

## Common commands

```sh
docker-compose exec app php artisan test
docker-compose exec app composer run quality   # Pint + PHPStan + tests
docker-compose exec app ./vendor/bin/pint
docker-compose exec app ./vendor/bin/phpstan analyse
docker-compose exec app php artisan migrate
docker-compose exec app php artisan scramble:export
docker-compose exec vite npm run build
```

Run a single test:

```sh
docker-compose exec app php artisan test --filter=PagesTest
```

Stop / reset:

```sh
docker-compose down
docker-compose down -v   # also deletes MySQL and Redis data volumes
```

## What is in place (Phase 0–9)

- Laravel 13 + PHP 8.4, Nginx, MySQL 8.4, Redis 7, Mailpit, queue worker, scheduler.
- Sanctum (`/api/v1` ready), Socialite config for Google, Livewire 4, TallStackUI 4, Heroicons.
- Tailwind CSS 4 with design tokens, TallStackUI integration, typography/forms plugins.
- Layouts: `public`, `auth`, `portal`, `admin`.
- Auth: register, login, Google OAuth, email verification, password reset, invitations, onboarding.
- Tenancy: organizations, memberships, roles, tenant switching, policies, isolation tests.
- Support core: tickets (numbering, status machine, SLA due dates), messages, internal notes, attachments, watchers, categories, tags, canned responses (data layer).
- Customer portal: dashboard, ticket list with status/search/pagination, create with attachments, detail timeline, reply.
- Agent/admin console: metric dashboard (open, unassigned, overdue, resolved 7d, first response, SLA compliance, 14-day volume, SLA risk), Livewire ticket queue (view tabs, filters, search, sort, pagination), Livewire ticket detail (reply, internal note, attachments, status transitions, assignment, classification, tags, watchers, canned responses, delete), master data CRUD (categories, tags, SLA policies, teams), member role/suspend/remove management, and filtered audit log.
- API: `/api/v1/auth/*` and `/api/v1/tickets*` (CRUD, assign, status, messages, attachments, watchers).
- Knowledge base, SLA automation, database/email notifications, signed webhooks, reporting, CSV export, plan throttling, cursor pagination, and MySQL FULLTEXT search.
- Demo seed data and a minimal Postman collection are available in `postman/`.
- Demo superadmin: `superadmin@supportflow.test` / `Password123`.
- Scramble OpenAPI docs, Pint, PHPStan/Larastan level 6, PHPUnit (150 tests, 595 assertions), GitHub Actions CI.

## Demo and release

See [docs/SMOKE-TEST.md](docs/SMOKE-TEST.md), [postman/SupportFlow.postman_collection.json](postman/SupportFlow.postman_collection.json), and [CHANGELOG.md](CHANGELOG.md).
