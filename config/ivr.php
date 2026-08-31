<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IVR Prompt Storage
    |--------------------------------------------------------------------------
    |
    | Prompts are written to the `ivr` disk (see config/filesystems.php) under
    | one directory per service, named with a zero-padded numeric prefix that
    | encodes playback order: {package}/001-welcome.wav. The telephony system
    | reads that directory in sorted order, so the prefix is the contract.
    |
    */

    'disk' => 'ivr',

    'max_upload_kilobytes' => 10240,

    /*
    |--------------------------------------------------------------------------
    | Accepted Audio Format
    |--------------------------------------------------------------------------
    |
    | Uploads are validated by parsing the WAV `fmt ` chunk, not by trusting the
    | file extension or MIME type. An empty list means that property is not
    | constrained at all — the telephony system is then trusted to cope with
    | whatever is uploaded, and a file it cannot play will fail during a call
    | rather than at upload time.
    |
    | Every property is currently unconstrained: prompts are produced elsewhere
    | in whatever format the telephony system is known to accept, and this app
    | does not second-guess them. All that remains is that the upload parses as
    | a RIFF/WAVE file at all, plus the size cap above.
    |
    | To re-enable a check, list the values it should accept. `encodings` holds
    | WAV format codes: 1 = PCM, 6 = A-law, 7 = mu-law.
    |
    */

    'audio' => [
        'encodings' => [],
        'sample_rates' => [],
        'bits_per_sample' => [],
        'channels' => [],
    ],

];
