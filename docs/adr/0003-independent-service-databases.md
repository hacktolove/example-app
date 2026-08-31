# One database per VAS service, and services are independent

Each VAS service (`news`, `sport`) now owns its own Postgres database, each carrying its
own `profiles` and `vas_subscription_history` tables, rather than all services sharing one
database and being distinguished by the `profiles.package` column. `serviceid` maps to a
connection in `config/vasws.php`, and `App\Support\ServiceStore` is the only place that
resolves a service to its connection.

This extends ADR-0001 rather than replacing it: these databases are still telco-owned and
this app is still a pure client of them. Migrations keep their `hasTable` guard so a
telco-provisioned database is never modified.

The trade-off we accepted: the shared-database design enforced "one active service per
MSISDN" *structurally* — one row per subscriber, one `package` column, so a second
subscription necessarily overwrote the first. Splitting the databases removes that
invariant by construction, which is the point: a subscriber may now hold any combination
of services at once. What we gave up is that any cross-service question is now an
application-level fan-out with no transaction spanning it — `displayservices`, `removeall`
and `history` each loop over every service connection, and `history` merge-sorts in PHP
instead of using an index-ordered scan.

We chose per-service history tables over one shared history table with a `service_id`
column. The shared table would keep `history` a single indexed query; per-service keeps
each service's data wholly self-contained, which matters more while the telco owns these
databases separately. At two services the fan-out cost is negligible. If the service count
grows enough that `history` approaches Selfcare's 5-second client timeout, a shared history
table is the first thing to revisit.

Alternatives rejected: keeping one database and adding a `service` column to `profiles`
(cheaper, but the telco owns that schema and we would be redefining its primary key
semantics); and keeping one active service per MSISDN across separate databases (pays the
full cost of the split while inheriting a distributed invariant that Postgres cannot
enforce).
