<?php

namespace Tests\Feature\Webhook;

use App\Models\WebhookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtnWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_event_stores_webhook_and_subscription(): void
    {
        $response = $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987654,
            'MSISDN' => '249999900046',
            'Status' => 'ACT-SB',
            'Price' => 0.00,
        ]));

        $response->assertOk();
        $response->assertSee('OK');

        $this->assertDatabaseHas('webhook_requests', [
            'method' => 'GET',
        ]);

        $this->assertDatabaseHas('mtn_subscriptions', [
            'channel_id' => 101,
            'operator_id' => 63401,
            'request_id' => 987654,
            'msisdn' => '249999900046',
            'status' => 'ACT-SB',
            'price' => '0.00',
        ]);
    }

    public function test_first_billing_success_stores_price(): void
    {
        $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987655,
            'MSISDN' => '249999900046',
            'Status' => 'FSC-BL',
            'Price' => 5.99,
        ]))->assertOk();

        $this->assertDatabaseHas('mtn_subscriptions', [
            'request_id' => 987655,
            'status' => 'FSC-BL',
            'price' => '5.99',
        ]);
    }

    public function test_renewal_billing_success_stores_price(): void
    {
        $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987656,
            'MSISDN' => '249999900046',
            'Status' => 'RSC-BL',
            'Price' => 5.99,
        ]))->assertOk();

        $this->assertDatabaseHas('mtn_subscriptions', [
            'request_id' => 987656,
            'status' => 'RSC-BL',
            'price' => '5.99',
        ]);
    }

    public function test_first_billing_failed_stores_zero_price(): void
    {
        $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987657,
            'MSISDN' => '249999900046',
            'Status' => 'FFL-BL',
            'Price' => 0.00,
        ]))->assertOk();

        $this->assertDatabaseHas('mtn_subscriptions', [
            'request_id' => 987657,
            'status' => 'FFL-BL',
            'price' => '0.00',
        ]);
    }

    public function test_unsubscription_event(): void
    {
        $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987658,
            'MSISDN' => '249999900046',
            'Status' => 'BLD-SB',
            'Price' => 0.00,
        ]))->assertOk();

        $this->assertDatabaseHas('mtn_subscriptions', [
            'request_id' => 987658,
            'status' => 'BLD-SB',
            'price' => '0.00',
        ]);
    }

    public function test_recycled_event(): void
    {
        $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987659,
            'MSISDN' => '249999900046',
            'Status' => 'RCL-SB',
            'Price' => 0.00,
        ]))->assertOk();

        $this->assertDatabaseHas('mtn_subscriptions', [
            'request_id' => 987659,
            'status' => 'RCL-SB',
            'price' => '0.00',
        ]);
    }

    public function test_webhook_request_stores_raw_payload_and_headers(): void
    {
        $this->withHeaders(['X-Custom' => 'test'])->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 987654,
            'MSISDN' => '249999900046',
            'Status' => 'ACT-SB',
            'Price' => 0.00,
        ]))->assertOk();

        $webhookRequest = WebhookRequest::first();

        $this->assertArrayHasKey('x-custom', $webhookRequest->headers);
        $this->assertNotNull($webhookRequest->ip_address);
        $this->assertEquals('101', $webhookRequest->payload['ChannelID']);
    }

    public function test_response_is_plain_text_ok(): void
    {
        $response = $this->getJson('/api/mtn/wh?'.http_build_query([
            'ChannelID' => 101,
            'OperatorID' => 63401,
            'RequestID' => 999999,
            'MSISDN' => '249999900046',
            'Status' => 'ACT-SB',
            'Price' => 0.00,
        ]));

        $response->assertOk();
        $this->assertEquals('OK', $response->getContent());
    }
}
