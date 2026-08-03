<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\VasServiceCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisplayServicesController extends Controller
{
    /**
     * Handle the incoming request.
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

        try {
            $profile = Profile::find($msisdn);
        } catch (Throwable $e) {
            Log::error('vasws displayservices failed', ['mdn' => $msisdn, 'exception' => $e]);

            return response()->json(['success' => false, 'msg' => 'system error', 'result' => 10, 'data' => []], 500);
        }

        $data = [];

        if ($profile && (int) $profile->status === 1) {
            $service = VasServiceCatalog::findByPackage((string) $profile->package);

            if ($service) {
                $data[] = [
                    'id' => $service['id'],
                    'englishname' => $service['english_name'],
                    'arabicname' => $service['arabic_name'],
                    'subscription_date' => $profile->subscribedAt()?->format('Y-m-d H:i:s'),
                    'subscription_channel' => $profile->channel,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'msg' => 'successful operation',
            'result' => 0,
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
