<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckSubController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $number = $request->query('number');
        $serviceId = $request->query('serviceid');

        if (! $number) {
            return response()->json(['error' => 'Missing number parameter'], 400);
        }

        if (! $serviceId) {
            return response()->json(['error' => 'Missing serviceid parameter'], 400);
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
            $profile = $service->profile($msisdn);
            $status = $profile ? (int) $profile->status : 0;

            return response()->json([
                'msisdn' => $msisdn,
                'serviceid' => $service->id,
                'status' => $status,
                'subscribed' => $status === 1,
            ]);
        } catch (Throwable $e) {
            Log::error('check-sub lookup failed', ['msisdn' => $msisdn, 'serviceid' => $serviceId, 'exception' => $e]);

            return response()->json(['error' => 'Database error'], 500);
        }
    }
}
