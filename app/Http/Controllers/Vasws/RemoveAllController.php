<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\VasSubscriptionHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveAllController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $mdn = $request->query('mdn');

        if (! $mdn) {
            return response()->json(['result' => 10, 'msg' => 'mdn is required', 'success' => false], 400);
        }

        $msisdn = Profile::normalizeMsisdn($mdn);

        if (! $msisdn) {
            return response()->json(['result' => 10, 'msg' => 'invalid mdn', 'success' => false], 400);
        }

        try {
            $profile = Profile::find($msisdn);

            if ($profile && (int) $profile->status === 1) {
                VasSubscriptionHistory::create([
                    'mdn' => $msisdn,
                    'package' => $profile->package,
                    'subscribed_at' => $profile->subscribedAt() ?? now(),
                    'subscribed_channel' => $profile->channel,
                    'unsubscribed_at' => now(),
                    'unsubscribed_channel' => 'vasws',
                ]);

                $profile->update([
                    'status' => 0,
                    'last_update_date' => now()->toDateString(),
                    'last_update_time' => now()->toTimeString(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('vasws removeall failed', ['mdn' => $msisdn, 'exception' => $e]);

            return response()->json(['result' => 10, 'msg' => 'system error', 'success' => false], 500);
        }

        return response()->json(['result' => 0, 'msg' => 'services have been removed successfully', 'success' => true]);
    }
}
