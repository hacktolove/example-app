# Daily KPI Dashboard — AI Agent Build Spec

## Overview
A business performance dashboard for a telecom VAS (Value-Added Services) platform.
Displays daily subscription, revenue, churn, and charging metrics across multiple services.
Single-page app (SPA). Stack: React + Recharts (or Chart.js). Currency: SDG (Sudanese Pound).

---

## Layout Structure

```
┌─────────────────────────────────────────┐
│ Header: title + date picker + nav tabs  │
├─────────────────────────────────────────┤
│ KPI Cards Row (4 cards)                 │
├─────────────────────────────────────────┤
│ Revenue by Service (bar chart)          │
├─────────────────────────────────────────┤
│ Subscriber Movement (grouped bar chart) │
├─────────────────────────────────────────┤
│ Daily Service Performance Table         │
├─────────────────────────────────────────┤
│ Charging Reach (grouped bar chart)      │
└─────────────────────────────────────────┘
```

---

## Header

| Element        | Detail |
|----------------|--------|
| Page title     | "Daily KPI Report" |
| Sub-label      | "Business dashboard — Performance reports" |
| Description    | "Revenue, subscribers, churn, and charging performance across all services." |
| Date picker    | Single date selector; default = today; label: "Daily report: {date}" |
| Currency badge | Static badge: "Currency: SDG" |
| Nav tabs       | `Daily summary` (active) · `Revenue` · `Churn` · `New subscribers` |
| Action buttons | `Refresh` (outline) · `Export CSV` (filled, orange) |

---

## KPI Cards (top row, 4 cards)

| Card Label           | Value (May 19 2026) | Description |
|----------------------|---------------------|-------------|
| Revenue today        | 2,022,289.00 SDG    | Successful paid charges on the selected day |
| Active subscribers   | 32,472              | Subscriber base as of the selected date |
| New subscribers      | 258                 | Unique users who started a subscription |
| Churn                | 33                  | Unique users who canceled in the period |

**Card UI rules:**
- Each card: label (top-left, muted) + tag/badge (top-right, bold) + large value + small description.
- Badge labels: `Revenue`, `Base`, `New`, `Churn`.
- Charge success rate is a 5th optional card: value `5.94%`, badge `1,929 unique`, description: "Unique charged users divided by active subscriber base."

---

## Charts

### 1. Revenue by Service (Bar Chart)
- **Title:** "Revenue by service"
- **Subtitle:** "Successful paid charges for {date}"
- **Type:** Vertical bar chart, single series
- **X-axis:** Service names — `Nafsi`, `Tabeebak`, `Rasael`, `Ghithaak`
- **Y-axis:** SDG value (0 – 2,500,000)
- **Color:** Blue (`#29ABE2` or similar)
- **Data (May 19 2026):**

| Service  | Revenue (SDG) |
|----------|---------------|
| Nafsi    | ~0            |
| Tabeebak | ~0            |
| Rasael   | ~0            |
| Ghithaak | ~2,022,289    |

---

### 2. Subscriber Movement (Grouped Bar Chart)
- **Title:** "Subscriber movement"
- **Subtitle:** "New subscribers compared with churn"
- **Type:** Grouped vertical bar chart, 2 series
- **X-axis:** Service names
- **Series:** `New subscribers` (green `#4CAF50`) · `Churn` (red/pink `#F44336`)
- **Data (May 19 2026):**

| Service  | New | Churn |
|----------|-----|-------|
| Nafsi    | 0   | 0     |
| Tabeebak | 0   | 0     |
| Rasael   | 0   | 0     |
| Ghithaak | 258 | 33    |

---

### 3. Charging Reach (Grouped Bar Chart)
- **Title:** "Charging reach"
- **Subtitle:** "Unique charged users compared with all successful charge attempts"
- **Type:** Grouped vertical bar chart, 3 series
- **Series:**
  - `Unique charged users` (orange `#FF9800`)
  - `Successful charges` (blue/purple `#5C6BC0`)
  - `Failed charges` (light grey `#B0BEC5`)
- **Data (May 19 2026):**

| Service  | Unique charged | Successful charges | Failed charges |
|----------|----------------|--------------------|----------------|
| Nafsi    | 0              | 0                  | 0              |
| Tabeebak | 0              | 0                  | 0              |
| Rasael   | 0              | 0                  | 0              |
| Ghithaak | ~1,929         | ~1,929             | ~100           |

---

## Daily Service Performance Table

- **Title:** "Daily service performance"
- **Subtitle:** "Use the status badges to quickly spot growth, churn, and charging issues."
- **Columns:** `Service` · `New subscribers` · `Churn` · `Net change`
- **Net change** formatting: positive = green `+N`, zero = grey `0`, negative = red `-N`
- **Data (May 19 2026):**

| Service  | New | Churn | Net change |
|----------|-----|-------|------------|
| Nafsi    | 0   | 0     | 0          |
| Tabeebak | 0   | 0     | 0          |
| Rasael   | 0   | 0     | 0          |
| Ghithaak | 258 | 33    | +225       |

---

## Services Master List

| Service   | Notes                          |
|-----------|--------------------------------|
| Nafsi     | Active service, 0 activity today |
| Tabeebak  | Active service, 0 activity today |
| Rasael    | Active service, 0 activity today |
| Ghithaak  | Primary active service today   |

---

## Data Model (props / API shape)

```typescript
interface DailyKpiReport {
  date: string;              // "2026-05-19"
  currency: string;          // "SDG"
  summary: {
    revenue: number;         // 2022289.00
    activeSubscribers: number; // 32472
    newSubscribers: number;  // 258
    churn: number;           // 33
    chargeSuccessRate: number; // 0.0594
    uniqueCharged: number;   // 1929
  };
  services: ServiceKpi[];
}

interface ServiceKpi {
  name: string;              // "Ghithaak"
  revenue: number;
  newSubscribers: number;
  churn: number;
  netChange: number;
  uniqueCharged: number;
  successfulCharges: number;
  failedCharges: number;
}
```

---

## Design System

| Token          | Value |
|----------------|-------|
| Primary orange | `#F5A623` |
| Primary blue   | `#29ABE2` |
| Background     | `#F5F5F5` (light grey page) |
| Card bg        | `#FFFFFF` |
| Card radius    | `12px` |
| Card shadow    | `0 2px 8px rgba(0,0,0,0.06)` |
| Text primary   | `#1A1A1A` |
| Text muted     | `#9E9E9E` |
| Success green  | `#4CAF50` |
| Danger red     | `#F44336` |

---

## Behavior Rules

- Date picker change → re-fetch all data for that date and re-render all charts + cards.
- `Export CSV` → downloads all visible data as a CSV file.
- `Refresh` → re-fetches data for the currently selected date.
- Charts must be responsive (resize with container width).
- Net change column: render `+N` in green, `-N` in red, `0` in grey.
- Revenue and charge values: format with thousands separator (e.g. `2,022,289.00`).
- Charge success rate: shown as percentage with 2 decimal places (`5.94%`).
