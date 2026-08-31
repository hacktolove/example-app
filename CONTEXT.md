# Example App

Telco subscription app. Handles MTN/Xceed subscription lifecycle webhooks and exposes subscriber-facing APIs.

## Language

**Service**:
A VAS product a subscriber can subscribe to independently — currently News and Sport — identified to Selfcare/CCS by its `serviceid`. Services are fully independent of one another: a subscriber may hold any combination of them at once, and subscribing to or leaving one never affects another. Each Service owns its own subscribers and its own unsubscription history; nothing is shared between Services.
_Avoid_: Package (that is a Service's short code, not the Service), product, offering

**Profile**:
A subscriber's current state *within one Service*, keyed by MSISDN — subscription status, channel, and lifecycle dates. There is at most one Profile per MSISDN per Service, and the same MSISDN may have independent Profiles in several Services at once. Profiles live on external telco-owned Postgres databases, separate from this app's own database.
_Avoid_: Subscriber, account, user

**MtnSubscription**:
A single parsed lifecycle event from the MTN/Xceed webhook (e.g. subscribe, billing success, unsubscribe). A log entry, not current state — many MtnSubscription rows can exist for one Profile over time.
_Avoid_: Subscription (ambiguous with Profile's subscription status), event

**MSISDN**:
A subscriber's phone number in normalized international form (e.g. `+249999900046`). The natural key for Profile — no surrogate ID exists.
_Avoid_: Number, phone, msisdn (lowercase in prose)

**IVR prompt**:
A piece of audio a caller hears, belonging to exactly one Service and holding a fixed place in that Service's playback sequence. Order is meaningful — prompts play first to last — so moving one changes what callers hear. Unlike Profiles, prompts are this application's own content, not telco-owned state.
_Avoid_: Recording, greeting, announcement, message

**Call flow**:
The ordered sequence of IVR prompts for one Service, taken as a whole. Each Service has exactly one call flow, and call flows are independent of one another in the same way Services are.
_Avoid_: Playlist, queue, script
