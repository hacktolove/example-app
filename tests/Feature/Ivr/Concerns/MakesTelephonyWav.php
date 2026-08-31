<?php

namespace Tests\Feature\Ivr\Concerns;

use Illuminate\Http\UploadedFile;

trait MakesTelephonyWav
{
    /**
     * Build a RIFF/WAVE file with the given format parameters.
     */
    protected function wav(
        int $sampleRate = 8000,
        int $bits = 16,
        int $channels = 1,
        int $audioFormat = 1,
        int $frames = 64,
    ): string {
        $blockAlign = $channels * intdiv($bits, 8);
        $byteRate = $sampleRate * $blockAlign;
        $data = str_repeat("\x00", $frames * $blockAlign);

        $fmt = pack('vvVVvv', $audioFormat, $channels, $sampleRate, $byteRate, $blockAlign, $bits);

        $body = 'WAVE'
            .'fmt '.pack('V', strlen($fmt)).$fmt
            .'data'.pack('V', strlen($data)).$data;

        return 'RIFF'.pack('V', strlen($body)).$body;
    }

    protected function uploadPrompt(string $name, int $serviceId = 1, ?string $contents = null): void
    {
        $this->post('/ivr', [
            'service_id' => $serviceId,
            'file' => UploadedFile::fake()->createWithContent($name, $contents ?? $this->wav()),
        ])->assertSessionHasNoErrors();
    }
}
