<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\VasSubscriptionHistory;
use App\Support\VasServiceCatalog;
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
        $service = VasServiceCatalog::find((int) $serviceId);

        if (! $msisdn || ! $service) {
            return response()->json([
                'result' => 10,
                'msg' => $msisdn ? 'invalid serviceid' : 'invalid mdn',
                'success' => false,
            ], 400);
        }

        try {
            $profile = Profile::find($msisdn);
            $alreadyOnThisService = $profile && (int) $profile->status === 1 && $profile->package === $service['package'];

            if ($alreadyOnThisService) {
                return response()->json(['result' => 1, 'msg' => 'already subscribed', 'success' => false]);
            }

            $switchingFromAnotherService = $profile && (int) $profile->status === 1;

            if ($switchingFromAnotherService) {
                VasSubscriptionHistory::create([
                    'mdn' => $msisdn,
                    'package' => $profile->package,
                    'subscribed_at' => $profile->subscribedAt() ?? now(),
                    'subscribed_channel' => $profile->channel,
                    'unsubscribed_at' => now(),
                    'unsubscribed_channel' => 'vasws',
                ]);
            }

            Profile::updateOrCreate(
                ['msisdn' => $msisdn],
                [
                    'package' => $service['package'],
                    'status' => 1,
                    'channel' => 'vasws',
                    'subs_date' => now()->toDateString(),
                    'subs_time' => now()->toTimeString(),
                    'last_update_date' => now()->toDateString(),
                    'last_update_time' => now()->toTimeString(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('vasws subscribe failed', ['mdn' => $msisdn, 'serviceid' => $serviceId, 'exception' => $e]);

            return response()->json(['result' => 10, 'msg' => 'system error', 'success' => false], 500);
        }

        return response()->json(['result' => 0, 'msg' => 'subscribed successfully', 'success' => true]);
    }
}
