# Example App

Telco subscription app. Handles MTN/Xceed subscription lifecycle webhooks and exposes subscriber-facing APIs.

## Language

**Profile**:
A subscriber's current state, keyed by MSISDN — package, language, channel, and subscription status. Lives on an external telco-owned Postgres database (`profiles` table), separate from this app's own database.
_Avoid_: Subscriber, account, user

**MtnSubscription**:
A single parsed lifecycle event from the MTN/Xceed webhook (e.g. subscribe, billing success, unsubscribe). A log entry, not current state — many MtnSubscription rows can exist for one Profile over time.
_Avoid_: Subscription (ambiguous with Profile's subscription status), event

**MSISDN**:
A subscriber's phone number in normalized international form (e.g. `+249999900046`). The natural key for Profile — no surrogate ID exists.
_Avoid_: Number, phone, msisdn (lowercase in prose)
