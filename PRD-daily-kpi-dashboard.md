# PRD: Daily KPI Dashboard

## Goal

Build an auth-protected, single-page Inertia dashboard that shows daily subscription, revenue, churn, and charging KPIs for the MTN VAS platform. Data source: `mtn_subscriptions` table via the `MtnSubscription` model.

---

## Decisions Made

| Decision | Choice | Rationale |
|---|---|---|
| Service breakdown | None for now — aggregate all rows | Only one service active; add per-service breakdown later |
| Active subscribers | Cumulative net: COUNT(ACT-SB) − COUNT(BLD-SB) up to selected date | Matches business definition |
| Unique charged users | DISTINCT msisdn WHERE status IN ('FSC-BL','RSC-BL') AND date = ? | Agreed definition |
| Billing success statuses | FSC-BL, RSC-BL | Revenue = SUM(price) for these statuses |
| Billing fail statuses | FFL-BL, RFL-BL | Used for failed charges count |
| Chart library | Recharts | React-idiomatic, already in stack ecosystem |
| Auth | Laravel auth middleware | Protected route |
| Tab content | Daily summary = cards + charts; Revenue/Churn/New subs tabs = 30-day time-series trends | Agreed |

---

## Status Code Reference

| Code | Meaning |
|---|---|
| ACT-SB | New subscription |
| BLD-SB | Unsubscribe / churn |
| FSC-BL | Billing success (forward) |
| RSC-BL | Billing success (renewal) |
| FFL-BL | Billing fail (forward) |
| RFL-BL | Billing fail (renewal) |
| RCL-SB | Recycle |

---

## API Shape

### `GET /api/dashboard/kpi?date=YYYY-MM-DD`

```typescript
interface DailyKpiResponse {
  date: string;
  currency: "SDG";
  summary: {
    revenue: number;             // SUM(price) WHERE status IN ('FSC-BL','RSC-BL') AND date = ?
    activeSubscribers: number;   // COUNT(ACT-SB up to date) - COUNT(BLD-SB up to date)
    newSubscribers: number;      // COUNT(DISTINCT msisdn) WHERE status='ACT-SB' AND date = ?
    churn: number;               // COUNT(DISTINCT msisdn) WHERE status='BLD-SB' AND date = ?
    chargeSuccessRate: number;   // uniqueCharged / activeSubscribers
    uniqueCharged: number;       // COUNT(DISTINCT msisdn) WHERE status IN ('FSC-BL','RSC-BL') AND date = ?
  };
  charging: {
    uniqueCharged: number;
    successfulCharges: number;   // COUNT(*) WHERE status IN ('FSC-BL','RSC-BL') AND date = ?
    failedCharges: number;       // COUNT(*) WHERE status IN ('FFL-BL','RFL-BL') AND date = ?
  };
}
```

### `GET /api/dashboard/trend?metric=revenue|churn|new_subscribers&days=30`

Returns 30 daily data points for the chosen metric up to today.

```typescript
interface TrendResponse {
  metric: string;
  points: { date: string; value: number }[];
}
```

---

## Frontend Pages & Components

```
resources/js/pages/Dashboard/
  Index.tsx              ← Inertia page entry, tab state manager
  components/
    DashboardHeader.tsx  ← title, date picker, nav tabs, Refresh + Export CSV
    KpiCards.tsx         ← 4 (+ 1 optional) summary cards
    RevenueChart.tsx     ← vertical bar chart (single series)
    SubscriberMovementChart.tsx  ← grouped bar (New vs Churn)
    ChargingReachChart.tsx       ← grouped bar (3 series)
    ServiceTable.tsx     ← Daily service performance table
    RevenueTrendChart.tsx        ← time-series for Revenue tab
    ChurnTrendChart.tsx          ← time-series for Churn tab
    NewSubsTrendChart.tsx        ← time-series for New subscribers tab
```

---

## Design Tokens

| Token | Value |
|---|---|
| Primary orange | `#F5A623` |
| Primary blue | `#29ABE2` |
| Success green | `#4CAF50` |
| Danger red | `#F44336` |
| Background | `#F5F5F5` |
| Card bg | `#FFFFFF`, radius `12px`, shadow `0 2px 8px rgba(0,0,0,0.06)` |

---

## Behavior Rules

- Date picker change → re-fetch KPI data, re-render all charts and cards.
- Tab change → show correct panel; trend tabs fetch `/api/dashboard/trend?metric=...`.
- Refresh button → re-fetch current date/tab data.
- Export CSV → download all visible data as CSV (client-side from last API response).
- Net change: `+N` green, `-N` red, `0` grey.
- Revenue: thousands separator, 2 decimal places.
- Charts: responsive (resize with container).

---

## Out of Scope (v1)

- Per-service breakdown (channel_id → name mapping)
- Date range picker (only single date)
- Push notifications / real-time updates
- Admin user management
