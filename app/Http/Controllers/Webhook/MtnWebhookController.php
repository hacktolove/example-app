<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\MtnSubscription;
use App\Models\WebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MtnWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        WebhookRequest::create([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'payload' => $request->input(),
            'headers' => $request->headers->all(),
            'ip_address' => $request->ip(),
        ]);

        MtnSubscription::create([
            'channel_id' => $request->input('ChannelID'),
            'operator_id' => $request->input('OperatorID'),
            'request_id' => $request->input('RequestID'),
            'msisdn' => $request->input('MSISDN'),
            'status' => $request->input('Status'),
            'price' => $request->input('Price', 0),
        ]);

        return response('OK');
    }
}
