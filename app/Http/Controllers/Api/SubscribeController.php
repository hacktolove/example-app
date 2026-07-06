<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
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

        if (! $number) {
            return response()->json(['error' => 'Missing number in JSON body'], 400);
        }

        $msisdn = Profile::normalizeMsisdn($number);

        if (! $msisdn) {
            return response()->json(['error' => 'Invalid number format'], 400);
        }

        try {
            $exists = Profile::where('msisdn', $msisdn)->exists();

            $profile = Profile::updateOrCreate(
                ['msisdn' => $msisdn],
                array_merge(
                    [
                        'status' => 1,
                        'last_update_date' => now()->toDateString(),
                        'last_update_time' => now()->toTimeString(),
                    ],
                    $exists ? [] : [
                        'channel' => 'api',
                        'subs_date' => now()->toDateString(),
                        'subs_time' => now()->toTimeString(),
                    ]
                )
            );

            $action = $exists ? 'update' : 'insert';

            return response()->json([
                'msisdn' => $profile->msisdn,
                'status' => 1,
                'action' => $action,
                'message' => "Subscription {$action}ed successfully",
            ]);
        } catch (Throwable $e) {
            Log::error('subscribe failed', ['msisdn' => $msisdn, 'exception' => $e]);

            return response()->json(['error' => 'Database error'], 500);
        }
    }
}
