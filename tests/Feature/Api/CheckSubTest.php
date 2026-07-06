<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckSubTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['sqlite', 'profiles'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_key' => 'test-api-key']);
    }

    private function headers(): array
    {
        return ['X-API-Key' => 'test-api-key'];
    }

    public function test_unknown_number_reports_not_subscribed(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/check-sub?'.http_build_query(['number' => '+249999900046']));

        $response->assertOk();
        $response->assertJson([
            'msisdn' => '+249999900046',
            'status' => 0,
            'subscribed' => false,
        ]);
    }

    public function test_subscribed_number_reports_subscribed(): void
    {
        Profile::create([
            'msisdn' => '+249999900046',
            'status' => 1,
            'channel' => 'api',
        ]);

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/check-sub?'.http_build_query(['number' => '+249999900046']));

        $response->assertOk();
        $response->assertJson([
            'msisdn' => '+249999900046',
            'status' => 1,
            'subscribed' => true,
        ]);
    }

    public function test_local_number_is_normalized_to_sudan_country_code(): void
    {
        Profile::create([
            'msisdn' => '+249999900046',
            'status' => 1,
            'channel' => 'api',
        ]);

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/check-sub?number=0999900046');

        $response->assertOk();
        $response->assertJson([
            'msisdn' => '+249999900046',
            'subscribed' => true,
        ]);
    }

    public function test_missing_number_returns_400(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/check-sub');

        $response->assertStatus(400);
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/check-sub?'.http_build_query(['number' => '+249999900046']));

        $response->assertStatus(401);
    }

    public function test_wrong_api_key_returns_401(): void
    {
        $response = $this->withHeaders(['X-API-Key' => 'wrong-key'])
            ->getJson('/api/check-sub?'.http_build_query(['number' => '+249999900046']));

        $response->assertStatus(401);
    }
}
