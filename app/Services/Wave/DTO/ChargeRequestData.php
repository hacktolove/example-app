<?php

namespace App\Services\Wave\DTO;

readonly class ChargeRequestData implements WaveRequestData
{
    public function __construct(
        public string $msisdn,
        public string $contentId,
        public string $amount,
    ) {}

    public function msisdn(): string
    {
        return $this->msisdn;
    }

    public function waveFunction(): string
    {
        return 'Charging';
    }

    public function toWaveRequestFields(): array
    {
        return [
            'MSISDN' => $this->msisdn,
            'ContentID' => $this->contentId,
            'Amount' => $this->amount,
        ];
    }
}
