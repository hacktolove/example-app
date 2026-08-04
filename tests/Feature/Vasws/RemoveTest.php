<?php

namespace Tests\Feature\Vasws;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['sqlite', 'profiles'];

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
        Profile::create([
            'msisdn' => '+249999900046',
            'package' => 'sport',
            'status' => 1,
            'channel' => 'vasws',
            'subs_date' => '2023-12-01',
            'subs_time' => '15:55:00',
        ]);

        $response = $this->authed('/vasws/remove?mdn=0999900046&serviceid=2');

        $response->assertOk();
        $response->assertJson(['result' => 0, 'msg' => 'unsubscribed successfully', 'success' => true]);

        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046', 'status' => 0], 'profiles');
        $this->assertDatabaseHas('vas_subscription_history', [
            'mdn' => '+249999900046',
            'package' => 'sport',
            'subscribed_channel' => 'vasws',
            'unsubscribed_channel' => 'vasws',
        ], 'profiles');
    }

    public function test_not_registered_returns_result_2(): void
    {
        $response = $this->authed('/vasws/remove?mdn=0999900046&serviceid=2');

        $response->assertOk();
        $response->assertJson(['result' => 2, 'msg' => 'subscriber is not registered in this service', 'success' => false]);
    }

    public function test_registered_to_different_service_returns_result_2(): void
    {
        Profile::create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'vasws',
        ]);

        $response = $this->authed('/vasws/remove?mdn=0999900046&serviceid=2');

        $response->assertOk();
        $response->assertJson(['result' => 2, 'success' => false]);
    }

    public function test_missing_auth_returns_401(): void
    {
        $response = $this->getJson('/vasws/remove?mdn=0999900046&serviceid=2');

        $response->assertStatus(401);
    }
}
