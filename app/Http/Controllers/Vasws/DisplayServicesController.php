<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisplayServicesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * Services are independent, so a subscriber may be active in any number of
     * them; every service store is checked.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $mdn = $request->query('mdn');

        if (! $mdn) {
            return response()->json(['success' => false, 'msg' => 'mdn is required', 'result' => 10, 'data' => []], 400);
        }

        $msisdn = Profile::normalizeMsisdn($mdn);

        if (! $msisdn) {
            return response()->json(['success' => false, 'msg' => 'invalid mdn', 'result' => 10, 'data' => []], 400);
        }

        $data = [];

        try {
            foreach (ServiceStore::all() as $service) {
                $profile = $service->activeProfile($msisdn);

                if (! $profile) {
                    continue;
                }

                $data[] = [
                    'id' => $service->id,
                    'englishname' => $service->englishName,
                    'arabicname' => $service->arabicName,
                    'subscription_date' => $profile->subscribedAt()?->format('Y-m-d H:i:s'),
                    'subscription_channel' => $profile->channel,
                ];
            }
        } catch (Throwable $e) {
            Log::error('vasws displayservices failed', ['mdn' => $msisdn, 'exception' => $e]);

            return response()->json(['success' => false, 'msg' => 'system error', 'result' => 10, 'data' => []], 500);
        }

        return response()->json([
            'success' => true,
            'msg' => 'successful operation',
            'result' => 0,
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
