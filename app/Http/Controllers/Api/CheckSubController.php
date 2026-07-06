<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
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

        if (! $number) {
            return response()->json(['error' => 'Missing number parameter'], 400);
        }

        $msisdn = Profile::normalizeMsisdn($number);

        if (! $msisdn) {
            return response()->json(['error' => 'Invalid number format'], 400);
        }

        try {
            $profile = Profile::find($msisdn);

            if ($profile) {
                $status = (int) $profile->status;

                return response()->json([
                    'msisdn' => $profile->msisdn,
                    'status' => $status,
                    'subscribed' => $status === 1,
                ]);
            }

            return response()->json([
                'msisdn' => $msisdn,
                'status' => 0,
                'subscribed' => false,
            ]);
        } catch (Throwable $e) {
            Log::error('check-sub lookup failed', ['msisdn' => $msisdn, 'exception' => $e]);

            return response()->json(['error' => 'Database error'], 500);
        }
    }
}
