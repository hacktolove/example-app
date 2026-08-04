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

## Pointing at the database

Subscriber state lives on the telco-owned Postgres database, reached through the existing
**`profiles`** Eloquent connection (shared with the check-sub/subscribe API). This service does
not create a parallel subscribers/services schema:

- **Current subscription state** — the `profiles` table. One active service per MSISDN, held in
  the `package` column, with `status = 1` meaning subscribed. Subscribing to a different service
  overwrites `package` (and logs the previous one to history).
- **Unsubscription history** — a `vas_subscription_history` table on the same `profiles`
  connection, written on every removal (see the migration
  `database/migrations/*_create_vas_subscription_history_table.php`). Each subscribe/unsubscribe
  cycle is a separate row, so `history` can return every past occurrence.

Configure the connection via `DB_PROFILES_*` in `.env`:

```env
DB_PROFILES_CONNECTION=pgsql
DB_PROFILES_HOST=127.0.0.1
DB_PROFILES_PORT=5432
DB_PROFILES_DATABASE=service_1     # the existing 50501/50502 subscription DB
DB_PROFILES_USERNAME=...
DB_PROFILES_PASSWORD=...
```

Then create the history table on that database:

```bash
php artisan migrate
```

`mdn` and `package` are indexed on `vas_subscription_history`, and `profiles` is keyed by
`msisdn`, to keep lookups under Selfcare's 5-second client timeout.

## Service catalog

The list of services (id → package code, English + Arabic names) is defined in
[`config/vasws.php`](config/vasws.php). Add or edit entries there; `package` must match the value
stored in `profiles.package` for that service.
