<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscribeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_key' => 'test-api-key']);
    }

    private function headers(): array
    {
        return ['X-API-Key' => 'test-api-key'];
    }

    public function test_new_number_is_inserted(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/subscribe', ['number' => '+249999900046', 'serviceid' => 1]);

        $response->assertOk();
        $response->assertJson([
            'msisdn' => '+249999900046',
            'serviceid' => 1,
            'status' => 1,
            'action' => 'insert',
        ]);

        $this->assertDatabaseHas('profiles', [
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 1,
            'channel' => 'api',
        ], 'news');
    }

    public function test_existing_number_is_updated(): void
    {
        Profile::on('news')->create([
            'msisdn' => '+249999900046',
            'package' => 'news',
            'status' => 0,
            'channel' => 'api',
        ]);

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/subscribe', ['number' => '+249999900046', 'serviceid' => 1]);

        $response->assertOk();
        $response->assertJson([
            'msisdn' => '+249999900046',
            'status' => 1,
            'action' => 'update',
        ]);

        $this->assertDatabaseHas('profiles', [
            'msisdn' => '+249999900046',
            'status' => 1,
        ], 'news');

        $this->assertEquals(1, Profile::on('news')->count());
    }

    public function test_subscribing_one_service_does_not_touch_another(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/subscribe', ['number' => '+249999900046', 'serviceid' => 2])
            ->assertOk();

        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046', 'status' => 1], 'sport');
        $this->assertDatabaseCount('profiles', 0, 'news');
    }

    public function test_missing_number_returns_400(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/subscribe', ['serviceid' => 1]);

        $response->assertStatus(400);
    }

    public function test_missing_serviceid_returns_400(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/subscribe', ['number' => '+249999900046']);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing serviceid in JSON body']);
    }

    public function test_unknown_serviceid_returns_400(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/subscribe', ['number' => '+249999900046', 'serviceid' => 999]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid serviceid']);
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/subscribe', ['number' => '+249999900046', 'serviceid' => 1]);

        $response->assertStatus(401);
    }

    public function test_wrong_api_key_returns_401(): void
    {
        $response = $this->withHeaders(['X-API-Key' => 'wrong-key'])
            ->postJson('/api/subscribe', ['number' => '+249999900046', 'serviceid' => 1]);

        $response->assertStatus(401);
    }
}
