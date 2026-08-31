<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaveBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wave.base_url' => 'https://wave.example.test',
            'wave.username' => 'wave-user',
            'wave.password' => 'wave-pass',
            'wave.timeout' => 10,
            'wave.origin_host_name' => 'example-app',
        ]);
    }

    public function test_balance_success(): void
    {
        Http::fake([
            '*/DiameterEventCharging' => Http::response([
                'responseCode' => 2001,
                'originTransactionID' => 'txn-123',
                'Balance' => '150.50',
                'WaveTransactionID' => 'wave-txn-456',
                'message' => 'Success',
            ]),
        ]);

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'code' => 2001,
            'message' => 'Success',
            'data' => [
                'balance' => '150.50',
                'wave_transaction_id' => 'wave-txn-456',
            ],
        ]);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://wave.example.test/DiameterEventCharging'
                && $request->hasHeader('Authorization')
                && $request['originNodeType'] === 'API'
                && $request['originHostName'] === 'example-app'
                && $request['module'] === 'DSC'
                && $request['command']['function'] === 'GetBalance'
                && $request['command']['request']['MSISDN'] === '249912345678'
                && $request['command']['request']['ContentID'] === '1000';
        });
    }

    public function test_balance_business_failure(): void
    {
        Http::fake([
            '*/DiameterEventCharging' => Http::response([
                'responseCode' => 4547,
                'message' => 'Insufficient balance',
            ]),
        ]);

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'code' => 4547,
            'message' => 'Insufficient balance',
        ]);
        $response->assertJsonMissing(['data']);
    }

    public function test_missing_msisdn_returns_422(): void
    {
        Http::fake();

        $response = $this->postJson('/api/v1/wave/balance', [
            'content_id' => '1000',
        ]);

        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_missing_content_id_returns_422(): void
    {
        Http::fake();

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
        ]);

        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_wave_authentication_failure_returns_401(): void
    {
        Http::fake([
            '*/DiameterEventCharging' => Http::response([], 401),
        ]);

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertStatus(401);
    }

    public function test_wave_authorization_failure_returns_403(): void
    {
        Http::fake([
            '*/DiameterEventCharging' => Http::response([], 403),
        ]);

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertStatus(403);
    }

    public function test_wave_server_error_returns_502(): void
    {
        Http::fake([
            '*/DiameterEventCharging' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertStatus(502);
    }

    public function test_wave_timeout_returns_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertStatus(503);
    }

    public function test_wave_network_failure_returns_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

        $response = $this->postJson('/api/v1/wave/balance', [
            'msisdn' => '249912345678',
            'content_id' => '1000',
        ]);

        $response->assertStatus(503);
    }
}
