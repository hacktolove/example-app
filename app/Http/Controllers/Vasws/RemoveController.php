<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $mdn = $request->query('mdn');
        $serviceId = $request->query('serviceid');

        if (! $mdn || ! $serviceId) {
            return response()->json([
                'result' => 10,
                'msg' => $mdn ? 'serviceid is required' : 'mdn is required',
                'success' => false,
            ], 400);
        }

        $msisdn = Profile::normalizeMsisdn($mdn);
        $service = ServiceStore::find((int) $serviceId);

        if (! $msisdn || ! $service) {
            return response()->json([
                'result' => 10,
                'msg' => $msisdn ? 'invalid serviceid' : 'invalid mdn',
                'success' => false,
            ], 400);
        }

        try {
            if (! $service->unsubscribe($msisdn, 'vasws')) {
                return response()->json(['result' => 2, 'msg' => 'subscriber is not registered in this service', 'success' => false]);
            }
        } catch (Throwable $e) {
            Log::error('vasws remove failed', ['mdn' => $msisdn, 'serviceid' => $serviceId, 'exception' => $e]);

            return response()->json(['result' => 10, 'msg' => 'system error', 'success' => false], 500);
        }

        return response()->json(['result' => 0, 'msg' => 'unsubscribed successfully', 'success' => true]);
    }
}
