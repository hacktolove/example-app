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

class HistoryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $mdn = $request->query('mdn');

        if (! $mdn) {
            return response()->json(['success' => false, 'msg' => 'mdn is required', 'data' => []], 400);
        }

        $msisdn = Profile::normalizeMsisdn($mdn);

        if (! $msisdn) {
            return response()->json(['success' => false, 'msg' => 'invalid mdn', 'data' => []], 400);
        }

        try {
            $history = VasSubscriptionHistory::where('mdn', $msisdn)
                ->orderBy('unsubscribed_at')
                ->get();
        } catch (Throwable $e) {
            Log::error('vasws history failed', ['mdn' => $msisdn, 'exception' => $e]);

            return response()->json(['success' => false, 'msg' => 'system error', 'data' => []], 500);
        }

        $data = $history->map(function (VasSubscriptionHistory $entry) {
            $service = VasServiceCatalog::findByPackage($entry->package);

            return [
                'serviceid' => $service['id'] ?? null,
                'englishname' => $service['english_name'] ?? null,
                'arabicname' => $service['arabic_name'] ?? null,
                'subscription_date' => $entry->subscribed_at->format('Y-m-d H:i:s'),
                'subscription_channel' => $entry->subscribed_channel,
                'unsubscription_date' => $entry->unsubscribed_at->format('Y-m-d H:i:s'),
                'unsubscription_channel' => $entry->unsubscribed_channel,
            ];
        });

        return response()->json([
            'success' => true,
            'msg' => 'successful operation',
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
