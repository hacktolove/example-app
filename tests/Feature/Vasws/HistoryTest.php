<?php

namespace Tests\Feature\Vasws;

use App\Models\VasSubscriptionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.vas_ws.username' => 'vasuser', 'app.vas_ws.password' => 'vaspass']);
    }

    private function authed(string $uri): TestResponse
    {
        return $this->withBasicAuth('vasuser', 'vaspass')->getJson($uri);
    }

    public function test_returns_every_terminated_cycle(): void
    {
        VasSubscriptionHistory::on('sport')->create([
            'mdn' => '+249999900046',
            'package' => 'sport',
            'subscribed_at' => '2023-12-01 15:55:00',
            'subscribed_channel' => 'sms',
            'unsubscribed_at' => '2023-12-05 12:40:00',
            'unsubscribed_channel' => 'ccs',
        ]);
        VasSubscriptionHistory::on('sport')->create([
            'mdn' => '+249999900046',
            'package' => 'sport',
            'subscribed_at' => '2024-01-01 09:00:00',
            'subscribed_channel' => 'vasws',
            'unsubscribed_at' => '2024-02-01 09:00:00',
            'unsubscribed_channel' => 'vasws',
        ]);

        $response = $this->authed('/vasws/history?mdn=0999900046');

        $response->assertOk();
        $response->assertJson(['success' => true, 'msg' => 'successful operation']);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'serviceid' => 2,
            'englishname' => 'Sport',
            'arabicname' => 'الرياضة',
            'subscription_date' => '2023-12-01 15:55:00',
            'subscription_channel' => 'sms',
            'unsubscription_date' => '2023-12-05 12:40:00',
            'unsubscription_channel' => 'ccs',
        ]);
    }

    public function test_merges_history_from_every_service_in_chronological_order(): void
    {
        VasSubscriptionHistory::on('sport')->create([
            'mdn' => '+249999900046',
            'package' => 'sport',
            'subscribed_at' => '2024-03-01 09:00:00',
            'subscribed_channel' => 'vasws',
            'unsubscribed_at' => '2024-04-01 09:00:00',
            'unsubscribed_channel' => 'vasws',
        ]);
        VasSubscriptionHistory::on('news')->create([
            'mdn' => '+249999900046',
            'package' => 'news',
            'subscribed_at' => '2024-01-01 09:00:00',
            'subscribed_channel' => 'ccs',
            'unsubscribed_at' => '2024-02-01 09:00:00',
            'unsubscribed_channel' => 'ccs',
        ]);

        $response = $this->authed('/vasws/history?mdn=0999900046');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $data = $response->json('data');
        $this->assertSame([1, 2], array_column($data, 'serviceid'));
        $this->assertSame('2024-02-01 09:00:00', $data[0]['unsubscription_date']);
        $this->assertSame('2024-04-01 09:00:00', $data[1]['unsubscription_date']);
    }

    public function test_returns_empty_data_when_no_history(): void
    {
        $response = $this->authed('/vasws/history?mdn=0999900046');

        $response->assertOk();
        $response->assertJson(['success' => true, 'data' => []]);
    }

    public function test_missing_mdn_returns_400(): void
    {
        $response = $this->authed('/vasws/history');

        $response->assertStatus(400);
    }

    public function test_missing_auth_returns_401(): void
    {
        $response = $this->getJson('/vasws/history?mdn=0999900046');

        $response->assertStatus(401);
    }
}
