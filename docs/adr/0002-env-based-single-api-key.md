# Single env-stored API key for check-sub/subscribe endpoints

`check-sub` and `subscribe` are protected by one static key in `APP_API_KEY` (`.env`), checked via an `X-API-Key` header, rather than a database-backed multi-key table (à la Sanctum personal access tokens). There is exactly one caller (the telco/Xceed integration) today, so per-key issuance, naming, and revocation tracking add complexity without a consumer. `php artisan api-key:generate` rotates the single key in place. If a second caller shows up, revisit in favor of a real `api_keys` table.
