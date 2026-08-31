<?php

namespace App\Http\Controllers\Wave;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wave\Concerns\HandlesWaveExceptions;
use App\Services\Wave\DTO\BalanceRequestData;
use App\Services\Wave\WaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetBalanceController extends Controller
{
    use HandlesWaveExceptions;

    public function __invoke(Request $request, WaveService $waveService): JsonResponse
    {
        $validated = $request->validate([
            'msisdn' => ['required', 'string'],
            'content_id' => ['required', 'string'],
        ]);

        return $this->callWave(fn () => $waveService->getBalance(new BalanceRequestData(
            msisdn: $validated['msisdn'],
            contentId: $validated['content_id'],
        )));
    }
}
