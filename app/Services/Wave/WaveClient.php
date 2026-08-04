<?php

namespace App\Services\Wave;

use App\Services\Wave\Exceptions\WaveAuthenticationException;
use App\Services\Wave\Exceptions\WaveAuthorizationException;
use App\Services\Wave\Exceptions\WaveServerException;
use App\Services\Wave\Exceptions\WaveUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WaveClient
{
    /**
     * Send a request envelope to the Wave Diameter Gateway.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function send(array $payload): array
    {
        try {
            $response = Http::withBasicAuth(config('wave.username'), config('wave.password'))
                ->acceptJson()
                ->timeout(config('wave.timeout'))
                ->post(rtrim((string) config('wave.base_url'), '/').'/DiameterEventCharging', $payload);
        } catch (ConnectionException $e) {
            throw new WaveUnavailableException($e->getMessage());
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status = $response->status();

        throw match ($status) {
            401 => new WaveAuthenticationException("Wave authentication failed (HTTP {$status})"),
            403 => new WaveAuthorizationException("Wave authorization failed (HTTP {$status})"),
            default => new WaveServerException("Wave server error (HTTP {$status})", $status),
        };
    }
}
