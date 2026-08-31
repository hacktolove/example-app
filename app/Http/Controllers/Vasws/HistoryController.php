<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\VasSubscriptionHistory;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class HistoryController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * History is stored per service, so entries are gathered from every
     * service store and merged into one chronological list.
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

        $entries = [];

        try {
            foreach (ServiceStore::all() as $service) {
                foreach ($service->history($msisdn) as $entry) {
                    $entries[] = [$service, $entry];
                }
            }
        } catch (Throwable $e) {
            Log::error('vasws history failed', ['mdn' => $msisdn, 'exception' => $e]);

            return response()->json(['success' => false, 'msg' => 'system error', 'data' => []], 500);
        }

        usort($entries, fn (array $a, array $b) => $a[1]->unsubscribed_at <=> $b[1]->unsubscribed_at);

        $data = collect($entries)->map(function (array $pair) {
            /** @var ServiceStore $service */
            /** @var VasSubscriptionHistory $entry */
            [$service, $entry] = $pair;

            return [
                'serviceid' => $service->id,
                'englishname' => $service->englishName,
                'arabicname' => $service->arabicName,
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
