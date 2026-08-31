<?php

namespace Tests\Feature\Vasws;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DisplayServicesTest extends TestCase
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

    public function test_returns_active_service_for_subscribed_mdn(): void
    {
        Profile::on('sport')->create([
            'msisdn' => '+249999900046',
            'package' => 'sport',
            'status' => 1,
            'channel' => 'vasws',
            'subs_date' => '2023-12-01',
            'subs_time' => '15:55:00',
        ]);

        $response = $this->authed('/vasws/displayservices?mdn=0999900046');

        $response->assertOk();
        $response->assertJson(['success' => true, 'msg' => 'successful operation', 'result' => 0]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'id' => 2,
            'englishname' => 'Sport',
            'arabicname' => 'الرياضة',
            'subscription_date' => '2023-12-01 15:55:00',
            'subscription_channel' => 'vasws',
        ]);
    }

    public function test_returns_every_service_the_subscriber_is_active_in(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'ccs',
            'subs_date' => '2024-01-10',
            'subs_time' => '08:00:00',
        ]);
        Profile::on('sport')->create([
            'msisdn' => '+249999900046',
            'package' => 'sport',
            'status' => 1,
            'channel' => 'vasws',
            'subs_date' => '2024-02-20',
            'subs_time' => '09:30:00',
        ]);

        $response = $this->authed('/vasws/displayservices?mdn=0999900046');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => 1, 'englishname' => 'News', 'subscription_channel' => 'ccs']);
        $response->assertJsonFragment(['id' => 2, 'englishname' => 'Sport', 'subscription_channel' => 'vasws']);
    }

    public function test_inactive_profile_in_one_service_is_excluded(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 0,
            'channel' => 'ccs',
        ]);
        Profile::on('sport')->create([
            'msisdn' => '+249999900046',
            'package' => 'sport',
            'status' => 1,
            'channel' => 'vasws',
        ]);

        $response = $this->authed('/vasws/displayservices?mdn=0999900046');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => 2]);
    }

    public function test_returns_empty_data_for_unsubscribed_mdn(): void
    {
        $response = $this->authed('/vasws/displayservices?mdn=0999900046');

        $response->assertOk();
        $response->assertJson(['success' => true, 'result' => 0, 'data' => []]);
    }

    public function test_missing_mdn_returns_400(): void
    {
        $response = $this->authed('/vasws/displayservices');

        $response->assertStatus(400);
    }

    public function test_missing_auth_returns_401(): void
    {
        $response = $this->getJson('/vasws/displayservices?mdn=0999900046');

        $response->assertStatus(401);
    }
}
