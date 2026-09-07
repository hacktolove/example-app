<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\VasSubscriptionHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * A single VAS service, together with the database that holds its subscribers.
 *
 * Every service owns an isolated store — its own `profiles` and
 * `vas_subscription_history` tables on its own connection — so subscribing to
 * one service never touches another. This class is the only place in the
 * application that names a service connection; nothing else should call
 * Profile or VasSubscriptionHistory directly.
 */
final class ServiceStore
{
    private function __construct(
        public readonly int $id,
        public readonly string $package,
        public readonly string $connection,
        public readonly string $englishName,
        public readonly string $arabicName,
    ) {}

    /**
     * Every service in the catalog, keyed by serviceid.
     *
     * @return array<int, self>
     */
    public static function all(): array
    {
        $stores = [];

        foreach (config('vasws.services', []) as $id => $service) {
            self::assertComplete($id, $service);

            $stores[(int) $id] = new self(
                id: (int) $id,
                package: $service['package'],
                connection: $service['connection'],
                englishName: $service['english_name'],
                arabicName: $service['arabic_name'],
            );
        }

        return $stores;
    }

    /**
     * Fail with something actionable when a catalog entry is incomplete.
     *
     * This runs during `migrate`, so an unhelpful error here lands in the
     * middle of a deploy. A stale config cache is by far the most common
     * cause: the deployed config/vasws.php is correct but never read.
     *
     * @param  array<string, mixed>  $service
     */
    private static function assertComplete(int|string $id, array $service): void
    {
        $missing = array_values(array_diff(
            ['package', 'connection', 'english_name', 'arabic_name'],
            array_keys($service)
        ));

        if ($missing === []) {
            return;
        }

        throw new RuntimeException(sprintf(
            'VAS service [%s] in config/vasws.php is missing: %s. '
            .'If that file looks correct, the config cache is stale — run `php artisan config:clear`.',
            $id,
            implode(', ', $missing)
        ));
    }

    /**
     * Resolve a service by the `serviceid` callers pass, or null if unknown.
     */
    public static function find(int $serviceId): ?self
    {
        return self::all()[$serviceId] ?? null;
    }

    /**
     * This service's connection names, for migrations and test setup.
     *
     * @return array<int, string>
     */
    public static function connections(): array
    {
        return array_values(array_map(
            fn (self $store) => $store->connection,
            self::all()
        ));
    }

    public function profile(string $msisdn): ?Profile
    {
        return Profile::on($this->connection)->find($msisdn);
    }

    /**
     * The subscriber's profile in this service, only if currently subscribed.
     */
    public function activeProfile(string $msisdn): ?Profile
    {
        $profile = $this->profile($msisdn);

        return $profile && $profile->status === 1 ? $profile : null;
    }

    public function isSubscribed(string $msisdn): bool
    {
        return $this->activeProfile($msisdn) !== null;
    }

    /**
     * Subscribe an MSISDN to this service.
     *
     * A subscriber who is not currently active starts a fresh subscription
     * period, so `subs_date`/`subs_time` and the channel are reset. An
     * already-active subscriber only has `last_update_*` touched, leaving the
     * original subscription date intact.
     *
     * @return bool True when the profile row did not exist beforehand.
     */
    public function subscribe(string $msisdn, string $channel): bool
    {
        $existing = $this->profile($msisdn);
        $startsNewPeriod = $existing === null || $existing->status !== 1;

        $attributes = [
            'package' => $this->package,
            'status' => 1,
            'last_update_date' => Carbon::now()->toDateString(),
            'last_update_time' => Carbon::now()->toTimeString(),
        ];

        if ($startsNewPeriod) {
            $attributes += [
                'channel' => $channel,
                'subs_date' => Carbon::now()->toDateString(),
                'subs_time' => Carbon::now()->toTimeString(),
            ];
        }

        Profile::on($this->connection)->updateOrCreate(['msisdn' => $msisdn], $attributes);

        return $existing === null;
    }

    /**
     * Unsubscribe an MSISDN from this service, recording the closed
     * subscription period in this service's history.
     *
     * @return bool False when the subscriber was not registered in this service.
     */
    public function unsubscribe(string $msisdn, string $channel): bool
    {
        $profile = $this->activeProfile($msisdn);

        if (! $profile) {
            return false;
        }

        VasSubscriptionHistory::on($this->connection)->create([
            'mdn' => $msisdn,
            'package' => $this->package,
            'subscribed_at' => $profile->subscribedAt() ?? Carbon::now(),
            'subscribed_channel' => $profile->channel,
            'unsubscribed_at' => Carbon::now(),
            'unsubscribed_channel' => $channel,
        ]);

        $profile->update([
            'status' => 0,
            'last_update_date' => Carbon::now()->toDateString(),
            'last_update_time' => Carbon::now()->toTimeString(),
        ]);

        return true;
    }

    /**
     * Closed subscription periods for this MSISDN in this service.
     *
     * @return Collection<int, VasSubscriptionHistory>
     */
    public function history(string $msisdn): Collection
    {
        return VasSubscriptionHistory::on($this->connection)
            ->where('mdn', $msisdn)
            ->orderBy('unsubscribed_at')
            ->get();
    }
}
