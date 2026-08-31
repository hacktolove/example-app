<?php

namespace App\Services\Wave\Exceptions;

class WaveServerException extends WaveException
{
    public function __construct(
        string $message,
        public readonly int $waveStatus,
    ) {
        parent::__construct($message);
    }
}
