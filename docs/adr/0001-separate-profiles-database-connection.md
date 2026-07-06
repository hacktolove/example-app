# Separate database connection for Profile

The `profiles` table lives on an external Postgres database owned by the telco/fusionpbx side, not this app's own database. We added a dedicated `profiles` Eloquent connection (its own `DB_PROFILES_*` env vars) rather than importing/migrating the data into the app's main database. This keeps the app a pure client of telco-owned subscriber state — it never becomes the source of truth for `profiles`, avoiding sync/ownership conflicts with whatever system (fusionpbx) writes to that table outside this app.
