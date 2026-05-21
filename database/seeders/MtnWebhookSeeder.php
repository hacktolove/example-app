<?php

namespace Database\Seeders;

use App\Models\MtnSubscription;
use App\Models\WebhookRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MtnWebhookSeeder extends Seeder
{
    private const CHANNEL_IDS = [101, 102, 103];

    private const OPERATOR_IDS = [63401, 63402];

    private const PRICE = 5.99;

    private int $requestIdSeq = 900000;

    public function run(): void
    {
        $today = Carbon::today();
        $msisdns = $this->generateMsisdns(300);
        $active = [];

        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $day = $today->copy()->subDays($daysAgo);

            // 8–20 new subscribers per day
            $newCount = random_int(8, 20);
            $newBatch = array_splice($msisdns, 0, $newCount);

            foreach ($newBatch as $msisdn) {
                $this->record('ACT-SB', $msisdn, 0, $day->copy()->addMinutes(random_int(0, 60)));
                $active[] = $msisdn;
            }

            if (empty($active)) {
                continue;
            }

            // Daily billing on ~80% of active subscribers
            $billingPool = (array) array_rand(array_flip($active), (int) max(1, (int) round(count($active) * 0.8)));

            foreach ($billingPool as $msisdn) {
                $billedAt = $day->copy()->addMinutes(random_int(60, 300));
                $isNew = in_array($msisdn, $newBatch, true);
                $success = random_int(1, 10) <= 8; // 80% success rate

                if ($success) {
                    $this->record($isNew ? 'FSC-BL' : 'RSC-BL', $msisdn, self::PRICE, $billedAt);
                } else {
                    $this->record($isNew ? 'FFL-BL' : 'RFL-BL', $msisdn, 0, $billedAt);
                }
            }

            // 2–6 churns per day
            $churnCount = random_int(2, min(6, count($active)));
            $churnKeys = (array) array_rand(array_flip($active), $churnCount);

            foreach ($churnKeys as $msisdn) {
                $this->record('BLD-SB', $msisdn, 0, $day->copy()->addMinutes(random_int(300, 480)));
                $active = array_values(array_filter($active, fn ($m) => $m !== $msisdn));
            }

            // 1–3 recycled events per day
            foreach (array_slice($active, 0, random_int(1, 3)) as $msisdn) {
                $this->record('RCL-SB', $msisdn, 0, $day->copy()->addMinutes(random_int(480, 600)));
            }
        }
    }

    private function record(string $status, string $msisdn, float $price, Carbon $createdAt): void
    {
        $requestId = $this->requestIdSeq++;
        $channelId = self::CHANNEL_IDS[array_rand(self::CHANNEL_IDS)];
        $operatorId = self::OPERATOR_IDS[array_rand(self::OPERATOR_IDS)];

        $params = [
            'ChannelID' => $channelId,
            'OperatorID' => $operatorId,
            'RequestID' => $requestId,
            'MSISDN' => $msisdn,
            'Status' => $status,
            'Price' => $price,
        ];

        WebhookRequest::create([
            'method' => 'GET',
            'url' => '/api/mtn/wh?'.http_build_query($params),
            'payload' => $params,
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Xceed/1.0'],
            'ip_address' => '196.203.'.random_int(1, 254).'.'.random_int(1, 254),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        MtnSubscription::create([
            'channel_id' => $channelId,
            'operator_id' => $operatorId,
            'request_id' => $requestId,
            'msisdn' => $msisdn,
            'status' => $status,
            'price' => $price,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /** @return list<string> */
    private function generateMsisdns(int $count): array
    {
        $msisdns = [];

        for ($i = 0; $i < $count; $i++) {
            $msisdns[] = '249'.str_pad((string) (900000000 + $i), 9, '0', STR_PAD_LEFT);
        }

        return $msisdns;
    }
}
