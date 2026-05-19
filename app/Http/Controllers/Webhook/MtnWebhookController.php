<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MtnWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        WebhookRequest::create([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'payload' => $request->input(),
            'headers' => $request->headers->all(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
