<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscribeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $number = $request->input('number');
        $serviceId = $request->input('serviceid');

        if (! $number) {
            return response()->json(['error' => 'Missing number in JSON body'], 400);
        }

        if (! $serviceId) {
            return response()->json(['error' => 'Missing serviceid in JSON body'], 400);
        }

        $msisdn = Profile::normalizeMsisdn($number);

        if (! $msisdn) {
            return response()->json(['error' => 'Invalid number format'], 400);
        }

        $service = ServiceStore::find((int) $serviceId);

        if (! $service) {
            return response()->json(['error' => 'Invalid serviceid'], 400);
        }

        try {
            $inserted = $service->subscribe($msisdn, 'api');
            $action = $inserted ? 'insert' : 'update';

            return response()->json([
                'msisdn' => $msisdn,
                'serviceid' => $service->id,
                'status' => 1,
                'action' => $action,
                'message' => "Subscription {$action}ed successfully",
            ]);
        } catch (Throwable $e) {
            Log::error('subscribe failed', ['msisdn' => $msisdn, 'serviceid' => $serviceId, 'exception' => $e]);

            return response()->json(['error' => 'Database error'], 500);
        }
    }
}
