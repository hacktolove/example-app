# IVR Prompts

Operator dashboard at `/ivr` for managing the audio a caller hears, per VAS service.
Sign-in required (the same `auth` + `verified` middleware as the rest of the dashboard).

Each service has one **call flow** — an ordered sequence of prompts, played first to last.
Services are independent, so a prompt added to News never affects Sport.

## How the telephony system finds them

Prompts are written to one directory per service, named with a zero-padded numeric prefix:

```
{IVR_AUDIO_ROOT}/
├── news/
│   ├── 001-welcome.wav
│   ├── 002-main-menu.wav
│   └── 003-goodbye.wav
└── sport/
    └── 001-kickoff.wav
```

The prefix **is** the ordering contract — the telephony system reads the directory in
sorted order, and nothing in this app's database is visible to it. Re-ordering prompts in
the dashboard therefore renames the files on disk. See
[`docs/adr/0004-filename-prefix-orders-ivr-prompts.md`](docs/adr/0004-filename-prefix-orders-ivr-prompts.md)
for why, and for what was traded away.

## Configuring the location

```env
IVR_AUDIO_ROOT=/srv/ivr/prompts
```

Defaults to `storage/app/ivr` when unset. Directory layout and the disk itself are defined
in `config/ivr.php` and `config/filesystems.php`.

**Permissions:** the web user needs write access to this path and the telephony user needs
read access. If the path is a shared mount, check both before go-live — a permission
problem surfaces at upload time, not at deploy time.

## Accepted audio

| Property | Required |
|---|---|
| Container | RIFF/WAVE |
| Encoding | Any |
| Sample rate | Any |
| Bit depth | Any |
| Channels | Any |
| Size | ≤ 10 MB |

Uploads are checked only far enough to confirm the file really is a WAV: the RIFF/WAVE
header is parsed rather than the extension or MIME type trusted, so a file renamed to
`.wav` is still rejected. Beyond that, nothing about the audio itself is constrained.

**This means an unplayable prompt fails during a live call, not at upload.** Prompts are
produced elsewhere, in whatever format the telephony system is known to accept, and this
app does not second-guess them — so getting the format right is the producer's
responsibility, not something the dashboard will catch.

To re-enable a check, list the values it should accept in `config/ivr.php`; an empty list
means no constraint. `encodings` holds WAV format codes (1 = PCM, 6 = A-law, 7 = mu-law).

```php
'audio' => [
    'encodings' => [1],
    'sample_rates' => [8000],
    'bits_per_sample' => [16],
    'channels' => [1],
],
```

## Adding a service

Nothing IVR-specific to do. The dashboard is driven by the service catalog in
`config/vasws.php`, so a new service appears with its own empty call flow automatically.
