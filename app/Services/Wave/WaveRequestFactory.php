<?php

namespace App\Services\Wave;

use App\Services\Wave\DTO\WaveRequestData;
use Illuminate\Support\Str;

class WaveRequestFactory
{
    /**
     * Build a Wave request envelope from an operation's DTO.
     *
     * @return array<string, mixed>
     */
    public function build(WaveRequestData $data): array
    {
        return [
            'originNodeType' => 'API',
            'originHostName' => config('wave.origin_host_name'),
            'originTransactionID' => (string) Str::uuid(),
            'originTimeStamp' => now()->utc()->toIso8601String(),
            'module' => 'DSC',
            'command' => [
                'function' => $data->waveFunction(),
                'request' => $data->toWaveRequestFields(),
            ],
        ];
    }
}
