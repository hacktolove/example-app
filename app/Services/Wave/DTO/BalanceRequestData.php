<?php

namespace App\Services\Wave\DTO;

readonly class BalanceRequestData implements WaveRequestData
{
    public function __construct(
        public string $msisdn,
        public string $contentId,
    ) {}

    public function msisdn(): string
    {
        return $this->msisdn;
    }

    public function waveFunction(): string
    {
        return 'GetBalance';
    }

    public function toWaveRequestFields(): array
    {
        return [
            'MSISDN' => $this->msisdn,
            'ContentID' => $this->contentId,
        ];
    }
}
