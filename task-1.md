# Task: Implement VAS Web Service (v1.5 spec)

Build a **VAS Web Service** — a web service that lets Sudani's Selfcare (mobile self-care app)
and CCS (customer care agent portal) manage subscriber registrations for our VAS services.
This service sits on our side; Selfcare/CCS call it over HTTP to subscribe/unsubscribe
customers and query their service state.

## Stack

- Laravel (PHP), following existing project conventions
- PostgreSQL as the backing store
- Assume this connects to (or extends) the existing `service_1` database used by the
  50501/50502 IVR subscription system — reuse the current subscribers/services/subscriptions
  schema where possible rather than creating a parallel one. If no matching tables exist yet,
  design a minimal schema covering: services (id, english name, arabic name), subscriptions
  (mdn, service id, subscribed_at, channel, active flag), and unsubscription history
  (mdn, service id, subscribed_at, subscribed_channel, unsubscribed_at, unsubscribed_channel).

## Auth

All endpoints require **HTTP Basic Authentication**. Credentials should be configurable via
`.env` (e.g. `VAS_WS_USERNAME`, `VAS_WS_PASSWORD`), not hardcoded. Reject unauthenticated
requests with `401`.

## General requirements

- All 6 endpoints are `GET` requests, URL-based: `/vasws/{function}?param1=value1&param2=value2`.
  This exact URL structure is specified by Selfcare/CCS as the caller — do not redesign it
  into a different REST layout.
- Parameter order in the URL must not matter.
- Every response must be valid JSON, UTF-8, matching the exact shapes below (including field
  names and types) — Selfcare/CCS parse these directly.
- Target response time under 5 seconds (Selfcare's client timeout) — add DB indexes on `mdn`
  and `service_id` accordingly.
- Arabic service names must round-trip correctly as UTF-8 JSON strings.
- Use the shared `result` code table below consistently for functions 2–5.

### Result codes
| Code | Meaning |
|---|---|
| 0 | Success |
| 1 | Subscriber is already registered in this service |
| 2 | Subscriber is not registered in this service |
| 10 | System error |

## Endpoints to implement

### 1. `GET /vasws/displayall`
List all available services. No parameters.

```json
{
  "success": true,
  "msg": "successful operation",
  "data": [
    { "id": 1, "englishname": "News", "arabicname": "الأخبار" }
  ]
}
```

### 2. `GET /vasws/displayservices?mdn={mdn}`
List a subscriber's currently active services.

```json
{
  "success": true,
  "msg": "successful operation",
  "result": 0,
  "data": [
    {
      "id": 1,
      "englishname": "Sport",
      "arabicname": "الرياضة",
      "subscription_date": "2023-12-01 15:55:00",
      "subscription_channel": "sms"
    }
  ]
}
```

### 3. `GET /vasws/subscribe?mdn={mdn}&serviceid={id}`
Subscribe an MDN to one service.

Success:
```json
{ "result": 0, "msg": "subscribed successfully", "success": true }
```
Already subscribed:
```json
{ "result": 1, "msg": "already subscribed", "success": false }
```

### 4. `GET /vasws/remove?mdn={mdn}&serviceid={id}`
Unsubscribe an MDN from one service. On removal, write a row into unsubscription history
(carrying forward the original subscription_date/channel plus the new unsubscription
date/channel).

Success:
```json
{ "result": 0, "msg": "unsubscribed successfully", "success": true }
```
Not registered:
```json
{ "result": 2, "msg": "subscriber is not registered in this service", "success": false }
```

### 5. `GET /vasws/removeall?mdn={mdn}`
Remove all of a subscriber's active services (each one logged to history same as #4).

```json
{ "result": 0, "msg": "services have been removed successfully", "success": true }
```

### 6. `GET /vasws/history?mdn={mdn}`
Return only *terminated* (unsubscribed) service records for the MDN — never active ones.
If a subscriber subscribed/unsubscribed from the same service multiple times, include every
occurrence as a separate entry.

```json
{
  "success": true,
  "msg": "successful operation",
  "data": [
    {
      "serviceid": 1,
      "englishname": "Sport",
      "arabicname": "الرياضة",
      "subscription_date": "2023-12-01 15:55:00",
      "subscription_channel": "sms",
      "unsubscription_date": "2023-12-05 12:40:00",
      "unsubscription_channel": "ccs"
    }
  ]
}
```

## Deliverables

1. Routes + controller(s) for the 6 endpoints under `/vasws/*`.
2. Basic-auth middleware (or Laravel's built-in `auth.basic` adapted to config-driven creds).
3. Migrations for any new tables, reusing existing `service_1` schema/models where they
   already cover this data.
4. Input validation: `mdn` and `serviceid` required where applicable, return `10`/system-error
   JSON with appropriate HTTP status on DB failure rather than a raw 500.
5. A short README section documenting how to configure the basic-auth credentials and how to
   point the service at the `service_1` (or new) database.
6. Basic feature tests covering: happy path for each endpoint, the "already subscribed" /
   "not registered" error paths, and the auth-rejection case.

## Out of scope

- Building the Selfcare/CCS client side — this task is the provider-side VAS Web Service only.
- No unrelated schema changes to the existing 50501/50502 IVR flow beyond what's needed to
  share/extend subscription data for this web service.
