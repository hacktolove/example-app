<?php

namespace App\Services\Wave;

use App\Services\Wave\DTO\BalanceRequestData;
use App\Services\Wave\DTO\ChargeRequestData;
use App\Services\Wave\DTO\LocationRequestData;
use App\Services\Wave\DTO\WaveRequestData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WaveService
{
    public function __construct(
        private readonly WaveRequestFactory $requestFactory,
        private readonly WaveClient $client,
        private readonly WaveResponseMapper $responseMapper,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getBalance(BalanceRequestData $data): array
    {
        return $this->execute($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function charge(ChargeRequestData $data): array
    {
        return $this->execute($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function location(LocationRequestData $data): array
    {
        return $this->execute($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function execute(WaveRequestData $data): array
    {
        $payload = $this->requestFactory->build($data);
        $function = $data->waveFunction();
        $context = [
            'request_id' => $payload['originTransactionID'],
            'endpoint' => request()?->path(),
            'function' => $function,
            'msisdn' => Str::mask($data->msisdn(), '*', 4, -2),
        ];
        $startedAt = microtime(true);

        try {
            $waveResponse = $this->client->send($payload);
        } catch (Throwable $e) {
            Log::error('Wave request failed', [
                ...$context,
                'duration_ms' => $this->elapsedMs($startedAt),
                'exception' => $e::class,
                'exception_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $mapped = $this->responseMapper->map($waveResponse, $function);

        Log::info('Wave request completed', [
            ...$context,
            'duration_ms' => $this->elapsedMs($startedAt),
            'http_status' => 200,
            'wave_response_code' => $mapped['code'],
        ]);

        return $mapped;
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
