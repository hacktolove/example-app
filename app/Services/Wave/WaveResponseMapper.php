<?php

namespace App\Services\Wave;

class WaveResponseMapper
{
    /**
     * Literal Wave response field names to try, keyed by logical name.
     *
     * Wave's real payload has not been confirmed against a live environment
     * (the spec only lists field names per operation, no full example) and
     * casing is inconsistent between operations (e.g. `message` on
     * GetBalance vs `Message` on Charging/Location). Adjust the literal
     * keys here once a real payload is available — nothing else in this
     * class should need to change.
     *
     * @var array<string, list<string>>
     */
    private const FIELD_ALIASES = [
        'response_code' => ['responseCode'],
        'origin_transaction_id' => ['originTransactionID'],
        'balance' => ['Balance'],
        'wave_transaction_id' => ['WaveTransactionID'],
        'message' => ['Message', 'message'],
        'location' => ['Location'],
    ];

    /**
     * Wave `responseCode` values in this range are treated as business
     * success (per the spec's 2001 = "Success" example); anything else,
     * including error codes like 4547, is a business failure. Unverified
     * against Wave's real code catalogue.
     */
    private const SUCCESS_CODE_MIN = 2000;

    private const SUCCESS_CODE_MAX = 2999;

    /**
     * Map a raw, flat Wave response into the application's standard shape.
     *
     * @param  array<string, mixed>  $waveResponse
     * @return array<string, mixed>
     */
    public function map(array $waveResponse, string $function): array
    {
        $code = (int) ($this->field($waveResponse, 'response_code') ?? 0);
        $success = $code >= self::SUCCESS_CODE_MIN && $code <= self::SUCCESS_CODE_MAX;

        $result = [
            'success' => $success,
            'code' => $code,
            'message' => $this->field($waveResponse, 'message') ?? ($success ? 'Success' : 'Unknown error'),
        ];

        if ($success) {
            $result['data'] = $this->dataFor($waveResponse, $function);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $waveResponse
     * @return array<string, string>
     */
    private function dataFor(array $waveResponse, string $function): array
    {
        $data = array_filter([
            'balance' => $this->field($waveResponse, 'balance'),
            'wave_transaction_id' => $this->field($waveResponse, 'wave_transaction_id'),
        ], fn (?string $value): bool => $value !== null);

        if ($function === 'Location') {
            $location = $this->field($waveResponse, 'location');

            if ($location !== null) {
                $data['location'] = $location;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $waveResponse
     */
    private function field(array $waveResponse, string $logicalField): ?string
    {
        foreach (self::FIELD_ALIASES[$logicalField] ?? [] as $key) {
            if (array_key_exists($key, $waveResponse) && $waveResponse[$key] !== null) {
                return (string) $waveResponse[$key];
            }
        }

        return null;
    }
}
