# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Development (all services concurrently):**
```
composer run dev
```
This starts: PHP dev server, queue worker, Pail log viewer, and Vite.

**Setup from scratch:**
```
composer run setup
```

**Tests:**
```
php artisan test --compact                              # all tests
php artisan test --compact tests/Feature/Foo.php       # single file
php artisan test --compact --filter=testName           # single test
```

**PHP linting (run after any PHP change):**
```
vendor/bin/pint --dirty --format agent
```

**Frontend:**
```
npm run dev          # Vite dev server
npm run build        # production build
npm run lint         # ESLint fix
npm run format       # Prettier fix
npm run types:check  # TypeScript check
```

## Architecture

**Stack:** Laravel 13 + Inertia v3 + React 19 + Tailwind v4, PHP 8.4, SQLite (dev), PHPUnit 12.

**Auth:** Handled entirely by `laravel/fortify`. Views are Inertia pages in `resources/js/pages/auth/`. Passkeys are supported via `@laravel/passkeys`. Fortify actions live in `app/Actions/Fortify/`.

**Frontend routing:** Pages live in `resources/js/pages/`. Layout is auto-assigned in `app.tsx` based on page name prefix (`auth/` → AuthLayout, `settings/` → AppLayout + SettingsLayout, else AppLayout, `welcome` → none). Route URLs are generated via Laravel Wayfinder — import from `@/actions/` (controller methods) or `@/routes/` (named routes). Do not hardcode URLs.

**API / Webhooks:** API routes in `routes/api.php`. The MTN webhook endpoint (`POST|GET /api/mtn/wh`) logs every raw request to `webhook_requests` and parses subscription events into `mtn_subscriptions`. Models use the `#[Fillable]` attribute (not the `$fillable` array).

**Key models:**
- `WebhookRequest` — raw inbound webhook log (method, url, payload, headers as JSON, ip)
- `MtnSubscription` — parsed MTN subscription event (channel_id, operator_id, request_id, msisdn, status, price)

**MTN status codes** (from `mtn-webhook-doc.md`): `ACT-SB` (subscribe), `FSC-BL`/`RSC-BL` (billing success), `FFL-BL`/`RFL-BL` (billing fail), `BLD-SB` (unsubscribe), `RCL-SB` (recycle).

**Boost MCP tools** (prefer over shell alternatives):
- `database-query` — read-only DB queries
- `database-schema` — inspect table structure
- `search-docs` — version-specific Laravel/package docs (always run before making code changes)
- `get-absolute-url` — resolve correct URL before sharing with user
- `browser-logs` — read browser console errors

## Conventions

- Use `php artisan make:` for all new files; pass `--no-interaction`.
- PHP: constructor property promotion, explicit return types, `#[Fillable]` attribute on models, PHPDoc with array shapes for complex types.
- Tests: PHPUnit classes only (no Pest). Feature tests use `RefreshDatabase`. Cover happy paths, failure paths, and edge cases.
- Every PHP file change → run Pint before finalizing.
- Every test change → run that test immediately; ask user before running the full suite.
