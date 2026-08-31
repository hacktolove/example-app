<?php

namespace App\Services\Wave\DTO;

interface WaveRequestData
{
    /**
     * The subscriber number this request concerns, for masked logging.
     */
    public function msisdn(): string;

    /**
     * The Wave `command.function` value for this operation.
     */
    public function waveFunction(): string;

    /**
     * The `command.request` fields for this operation, in Wave's own casing.
     *
     * @return array<string, string>
     */
    public function toWaveRequestFields(): array;
}
