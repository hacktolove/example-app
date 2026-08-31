<?php

namespace App\Http\Controllers\Wave;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wave\Concerns\HandlesWaveExceptions;
use App\Services\Wave\DTO\ChargeRequestData;
use App\Services\Wave\WaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    use HandlesWaveExceptions;

    public function __invoke(Request $request, WaveService $waveService): JsonResponse
    {
        $validated = $request->validate([
            'msisdn' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'content_id' => ['required', 'string'],
        ]);

        return $this->callWave(fn () => $waveService->charge(new ChargeRequestData(
            msisdn: $validated['msisdn'],
            contentId: $validated['content_id'],
            amount: (string) $validated['amount'],
        )));
    }
}
