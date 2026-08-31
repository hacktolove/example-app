<?php

namespace App\Http\Controllers\Vasws;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\ServiceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveAllController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * Each service holds its own subscribers, so removing everything means
     * unsubscribing from every service store the subscriber is active in.
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
            foreach (ServiceStore::all() as $service) {
                $service->unsubscribe($msisdn, 'vasws');
            }
        } catch (Throwable $e) {
            Log::error('vasws removeall failed', ['mdn' => $msisdn, 'exception' => $e]);

            return response()->json(['result' => 10, 'msg' => 'system error', 'success' => false], 500);
        }

        return response()->json(['result' => 0, 'msg' => 'services have been removed successfully', 'success' => true]);
    }
}
