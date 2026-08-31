<?php

namespace Tests\Feature\Vasws;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SubscribeTest extends TestCase
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

    public function test_subscribes_new_mdn(): void
    {
        $response = $this->authed('/vasws/subscribe?mdn=0999900046&serviceid=1');

        $response->assertOk();
        $response->assertJson(['result' => 0, 'msg' => 'subscribed successfully', 'success' => true]);

        $this->assertDatabaseHas('profiles', [
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'vasws',
        ], 'news');
    }

    public function test_already_subscribed_to_same_service_returns_result_1(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'vasws',
        ]);

        $response = $this->authed('/vasws/subscribe?mdn=0999900046&serviceid=1');

        $response->assertOk();
        $response->assertJson(['result' => 1, 'msg' => 'already subscribed', 'success' => false]);
    }

    public function test_subscribing_to_a_second_service_leaves_the_first_untouched(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'vasws',
            'subs_date' => '2023-12-01',
            'subs_time' => '15:55:00',
        ]);

        $response = $this->authed('/vasws/subscribe?mdn=0999900046&serviceid=2');

        $response->assertOk();
        $response->assertJson(['result' => 0, 'success' => true]);

        $this->assertDatabaseHas('profiles', [
            'msisdn' => '+249999900046',
            'package' => 'sport',
            'status' => 1,
        ], 'sport');

        $news = Profile::on('news')->find('+249999900046');
        $this->assertSame('news', $news->package);
        $this->assertSame(1, $news->status);
        $this->assertSame('2023-12-01', $news->subs_date->toDateString());
    }

    public function test_subscribing_to_a_second_service_writes_no_history(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'vasws',
        ]);

        $this->authed('/vasws/subscribe?mdn=0999900046&serviceid=2')->assertOk();

        $this->assertDatabaseCount('vas_subscription_history', 0, 'news');
        $this->assertDatabaseCount('vas_subscription_history', 0, 'sport');
    }

    public function test_resubscribing_after_removal_starts_a_new_subscription_period(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 0,
            'channel' => 'ccs',
            'subs_date' => '2023-12-01',
            'subs_time' => '15:55:00',
        ]);

        $this->authed('/vasws/subscribe?mdn=0999900046&serviceid=1')->assertOk();

        $news = Profile::on('news')->find('+249999900046');
        $this->assertSame(1, $news->status);
        $this->assertSame('vasws', $news->channel);
        $this->assertSame(now()->toDateString(), $news->subs_date->toDateString());
    }

    public function test_missing_serviceid_returns_400(): void
    {
        $response = $this->authed('/vasws/subscribe?mdn=0999900046');

        $response->assertStatus(400);
    }

    public function test_invalid_serviceid_returns_400(): void
    {
        $response = $this->authed('/vasws/subscribe?mdn=0999900046&serviceid=999');

        $response->assertStatus(400);
    }

    public function test_missing_auth_returns_401(): void
    {
        $response = $this->getJson('/vasws/subscribe?mdn=0999900046&serviceid=1');

        $response->assertStatus(401);
    }
}
