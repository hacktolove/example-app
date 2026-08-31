<?php

namespace Tests\Feature\Vasws;

use App\Models\Profile;
use App\Models\VasSubscriptionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RemoveAllTest extends TestCase
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

    public function test_removes_active_subscription_and_logs_history(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'vasws',
            'subs_date' => '2023-12-01',
            'subs_time' => '15:55:00',
        ]);

        $response = $this->authed('/vasws/removeall?mdn=0999900046');

        $response->assertOk();
        $response->assertJson(['result' => 0, 'msg' => 'services have been removed successfully', 'success' => true]);

        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046', 'status' => 0], 'news');
        $this->assertDatabaseHas('vas_subscription_history', ['mdn' => '+249999900046', 'package' => 'news'], 'news');
    }

    public function test_removes_the_subscriber_from_every_service(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'ccs',
        ]);
        Profile::on('sport')->create([
            'msisdn' => '+249999900046',
            'package' => 'sport',
            'status' => 1,
            'channel' => 'vasws',
        ]);

        $this->authed('/vasws/removeall?mdn=0999900046')->assertOk();

        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046', 'status' => 0], 'news');
        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046', 'status' => 0], 'sport');
        $this->assertDatabaseCount('vas_subscription_history', 1, 'news');
        $this->assertDatabaseCount('vas_subscription_history', 1, 'sport');
    }

    public function test_succeeds_when_no_active_subscription(): void
    {
        $response = $this->authed('/vasws/removeall?mdn=0999900046');

        $response->assertOk();
        $response->assertJson(['result' => 0, 'success' => true]);

        $this->assertSame(0, VasSubscriptionHistory::on('news')->count());
        $this->assertSame(0, VasSubscriptionHistory::on('sport')->count());
    }

    public function test_missing_mdn_returns_400(): void
    {
        $response = $this->authed('/vasws/removeall');

        $response->assertStatus(400);
    }

    public function test_missing_auth_returns_401(): void
    {
        $response = $this->getJson('/vasws/removeall?mdn=0999900046');

        $response->assertStatus(401);
    }
}
