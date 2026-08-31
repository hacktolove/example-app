# VAS Web Service

Provider-side web service that lets Sudani **Selfcare** and **CCS** manage subscriber
registrations for VAS services. Selfcare/CCS call these endpoints over HTTP to subscribe,
unsubscribe, and query subscriber state.

All endpoints are `GET`, URL-based (`/vasws/{function}?param=value`), return UTF-8 JSON,
and are protected by HTTP Basic Authentication. See [`openapi-vasws.yaml`](openapi-vasws.yaml)
for the full contract.

| Function | URL |
|---|---|
| List all services | `GET /vasws/displayall` |
| List a subscriber's active services | `GET /vasws/displayservices?mdn={mdn}` |
| Subscribe | `GET /vasws/subscribe?mdn={mdn}&serviceid={id}` |
| Unsubscribe | `GET /vasws/remove?mdn={mdn}&serviceid={id}` |
| Unsubscribe from everything | `GET /vasws/removeall?mdn={mdn}` |
| Unsubscription history | `GET /vasws/history?mdn={mdn}` |

## Result codes (functions 2–5)

| Code | Meaning |
|---|---|
| 0 | Success |
| 1 | Subscriber is already registered in this service |
| 2 | Subscriber is not registered in this service |
| 10 | System error |

## Configuring Basic Auth credentials

Credentials are read from the environment, never hardcoded. Set them in `.env`:

```env
VAS_WS_USERNAME=selfcare
VAS_WS_PASSWORD=<a-strong-secret>
```

They are surfaced via `config('app.vas_ws.username')` / `config('app.vas_ws.password')` and
enforced by the `vasws.auth` middleware (`App\Http\Middleware\VerifyVasWsBasicAuth`). Any
request with a missing or wrong username/password gets `401` with a
`WWW-Authenticate: Basic realm="vasws"` header. If either credential is left blank, **all**
requests are rejected (fail-closed).

Example call:

```bash
curl -u selfcare:secret "https://your-app.example/vasws/displayall"
```

## Pointing at the databases

Every service owns its own telco-owned Postgres database, reached through its own
Eloquent connection. Services are independent: a subscriber may be registered in any
combination of them at once, and subscribing to or leaving one never touches another.

Each service database carries the same two tables:

- **Current subscription state** — the `profiles` table, with `status = 1` meaning
  subscribed. `package` holds the service's short code, and is kept for the telco's own
  tooling even though the database already implies the service.
- **Unsubscription history** — a `vas_subscription_history` table, written on every
  removal. Each subscribe/unsubscribe cycle is a separate row, so `history` can return
  every past occurrence. `history` reads from all service databases and merges them into
  one chronological list.

`serviceid` maps to a connection in [`config/vasws.php`](config/vasws.php); the
connections themselves are defined in `config/database.php`. Configure them in `.env`:

```env
DB_NEWS_CONNECTION=pgsql
DB_NEWS_HOST=127.0.0.1
DB_NEWS_PORT=5432
DB_NEWS_DATABASE=service_1     # the existing 50501/50502 subscription DB
DB_NEWS_USERNAME=...
DB_NEWS_PASSWORD=...

DB_SPORT_CONNECTION=pgsql
DB_SPORT_HOST=127.0.0.1
DB_SPORT_PORT=5432
DB_SPORT_DATABASE=service_2
DB_SPORT_USERNAME=...
DB_SPORT_PASSWORD=...
```

Then create the tables on any database that doesn't already have them:

```bash
php artisan migrate
```

Migrations skip a table that already exists, so pointing at a telco-provisioned database
is safe. `mdn` and `package` are indexed on `vas_subscription_history`, and `profiles` is
keyed by `msisdn`, to keep lookups under Selfcare's 5-second client timeout.

Application code never names a connection directly — `App\Support\ServiceStore` resolves
a `serviceid` to its store, and is the only place that knows connections exist.

## Service catalog

The list of services — id, package code, connection, English + Arabic names — is defined
in [`config/vasws.php`](config/vasws.php). `package` must match the value stored in that
service's `profiles.package` column, and `connection` must name a connection defined in
`config/database.php`.

### Adding a service

1. Add an entry to `config/vasws.php` with its `package` and `connection`.
2. Add a `'<connection>' => [...]` block to `config/database.php` and the matching
   `DB_<NAME>_*` variables to `.env`.
3. Run `php artisan migrate` to create the two tables on the new database.
4. On any environment that caches config, run `php artisan config:clear` (or
   `optimize:clear`) **before** migrating. The catalog is read from config, so a cache
   built before the change makes `migrate` fail on the old entries.

No application code changes; every endpoint is catalog-driven.
