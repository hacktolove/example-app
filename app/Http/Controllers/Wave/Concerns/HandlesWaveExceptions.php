<?php

namespace App\Http\Controllers\Wave\Concerns;

use App\Services\Wave\Exceptions\WaveAuthenticationException;
use App\Services\Wave\Exceptions\WaveAuthorizationException;
use App\Services\Wave\Exceptions\WaveServerException;
use App\Services\Wave\Exceptions\WaveUnavailableException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesWaveExceptions
{
    /**
     * Run a Wave operation, translating gateway failures into the app's
     * HTTP responses. Business failures (Wave 2xx with a failure code)
     * are not exceptions and pass through as normal 200 JSON.
     */
    protected function callWave(Closure $operation): JsonResponse
    {
        try {
            return response()->json($operation());
        } catch (WaveAuthenticationException) {
            return response()->json(['error' => 'Unauthorized'], 401);
        } catch (WaveAuthorizationException) {
            return response()->json(['error' => 'Forbidden'], 403);
        } catch (WaveServerException) {
            return response()->json(['error' => 'Bad Gateway'], 502);
        } catch (WaveUnavailableException) {
            return response()->json(['error' => 'Service Unavailable'], 503);
        } catch (Throwable $e) {
            Log::error('Unexpected error handling Wave request', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
