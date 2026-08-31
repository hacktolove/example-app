<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that an upload is a WAV file the telephony system can actually
 * play, by parsing the RIFF `fmt ` chunk rather than trusting the extension
 * or MIME type. A 44.1kHz stereo file renamed to .wav passes every superficial
 * check and then fails silently during playback, which is the worst outcome
 * available — it looks like a successful upload.
 */
class TelephonyWavFile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute must be a valid uploaded file.');

            return;
        }

        $format = $this->readWavFormat((string) $value->getRealPath());

        if ($format === null) {
            $fail('The :attribute must be a WAV file. The file could not be read as RIFF/WAVE audio.');

            return;
        }

        // An empty list in config/ivr.php means that property is not constrained.
        $expectedEncodings = array_values((array) config('ivr.audio.encodings'));
        $expectedRates = array_values((array) config('ivr.audio.sample_rates'));
        $expectedBits = array_values((array) config('ivr.audio.bits_per_sample'));
        $expectedChannels = array_values((array) config('ivr.audio.channels'));

        if ($expectedEncodings !== [] && ! in_array($format['audio_format'], $expectedEncodings, true)) {
            $fail(sprintf(
                'The :attribute must use a supported WAV encoding (format code %s); this file is code %d.',
                implode(' or ', $expectedEncodings),
                $format['audio_format']
            ));

            return;
        }

        if ($expectedRates !== [] && ! in_array($format['sample_rate'], $expectedRates, true)) {
            $fail(sprintf(
                'The :attribute must be sampled at %s Hz; this file is %d Hz.',
                implode(' or ', $expectedRates),
                $format['sample_rate']
            ));

            return;
        }

        if ($expectedBits !== [] && ! in_array($format['bits_per_sample'], $expectedBits, true)) {
            $fail(sprintf(
                'The :attribute must be %s-bit audio; this file is %d-bit.',
                implode(' or ', $expectedBits),
                $format['bits_per_sample']
            ));

            return;
        }

        if ($expectedChannels !== [] && ! in_array($format['channels'], $expectedChannels, true)) {
            $fail(sprintf(
                'The :attribute must be %s; this file has %d channels.',
                implode(' or ', array_map(
                    fn (int $c) => $c === 1 ? 'mono' : "{$c}-channel",
                    $expectedChannels
                )),
                $format['channels']
            ));
        }
    }

    /**
     * Parse the `fmt ` chunk of a RIFF/WAVE file.
     *
     * Chunks are walked rather than read at a fixed offset, because `fmt ` is
     * conventionally but not necessarily the first chunk after the header.
     *
     * @return array{audio_format: int, channels: int, sample_rate: int, bits_per_sample: int}|null
     */
    private function readWavFormat(string $path): ?array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $header = fread($handle, 12);

            if ($header === false || strlen($header) < 12) {
                return null;
            }

            if (substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WAVE') {
                return null;
            }

            while (! feof($handle)) {
                $chunkHeader = fread($handle, 8);

                if ($chunkHeader === false || strlen($chunkHeader) < 8) {
                    return null;
                }

                $chunkId = substr($chunkHeader, 0, 4);
                /** @var array{size: int} $unpacked */
                $unpacked = unpack('Vsize', substr($chunkHeader, 4, 4));
                $chunkSize = $unpacked['size'];

                if ($chunkId === 'fmt ') {
                    return $this->parseFormatChunk($handle, $chunkSize);
                }

                // Chunks are word-aligned: an odd size is followed by a pad byte.
                if (fseek($handle, $chunkSize + ($chunkSize % 2), SEEK_CUR) !== 0) {
                    return null;
                }
            }

            return null;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @return array{audio_format: int, channels: int, sample_rate: int, bits_per_sample: int}|null
     */
    private function parseFormatChunk($handle, int $chunkSize): ?array
    {
        if ($chunkSize < 16) {
            return null;
        }

        $chunk = fread($handle, 16);

        if ($chunk === false || strlen($chunk) < 16) {
            return null;
        }

        /** @var array{format: int, channels: int, rate: int, bits: int}|false $fields */
        $fields = unpack('vformat/vchannels/Vrate/x6/vbits', $chunk);

        if ($fields === false) {
            return null;
        }

        return [
            'audio_format' => $fields['format'],
            'channels' => $fields['channels'],
            'sample_rate' => $fields['rate'],
            'bits_per_sample' => $fields['bits'],
        ];
    }
}
