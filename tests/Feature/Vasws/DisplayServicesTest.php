<?php

namespace Tests\Feature\Vasws;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DisplayServicesTest extends TestCase
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

    public function test_returns_active_service_for_subscribed_mdn(): void
    {
        Profile::create([
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
        $response->assertJsonFragment([
            'id' => 2,
            'englishname' => 'Sport',
            'arabicname' => 'الرياضة',
            'subscription_date' => '2023-12-01 15:55:00',
            'subscription_channel' => 'vasws',
        ]);
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
