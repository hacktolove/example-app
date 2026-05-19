<?php

namespace Tests\Feature\Webhook;

use App\Models\WebhookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtnWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_request_stores_webhook_request(): void
    {
        $response = $this->postJson('/api/mtn/wh', [
            'event' => 'payment.completed',
            'data' => ['reference' => 'TXN123', 'amount' => 5000],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('webhook_requests', [
            'method' => 'POST',
            'url' => 'http://localhost/api/mtn/wh',
        ]);
    }

    public function test_get_request_stores_webhook_request(): void
    {
        $response = $this->getJson('/api/mtn/wh?foo=bar');

        $response->assertOk();

        $this->assertDatabaseHas('webhook_requests', [
            'method' => 'GET',
        ]);
    }

    public function test_stores_headers_and_ip(): void
    {
        $response = $this->withHeaders([
            'X-Signature' => 'abc123',
        ])->postJson('/api/mtn/wh', ['test' => true]);

        $response->assertOk();

        $webhookRequest = WebhookRequest::first();

        $this->assertArrayHasKey('x-signature', $webhookRequest->headers);
        $this->assertNotNull($webhookRequest->ip_address);
    }

    public function test_stores_empty_payload(): void
    {
        $response = $this->postJson('/api/mtn/wh');

        $response->assertOk();

        $this->assertEquals(1, WebhookRequest::count());
    }
}
