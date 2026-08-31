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

**Permissions.** The web user must be able to create and write inside this path; the
telephony user must be able to read it. Service directories are created `0755` and files
`0644` (set explicitly on the disk — Flysystem's default `0700` directory would deny the
telephony user traversal, and that failure shows up as a silent prompt during a call
rather than as an error at upload). Tighten to `0750`/`0640` via the `permissions` key in
`config/filesystems.php` if the telephony user can be placed in a shared group.

**Upload size.** The dashboard advertises 10 MB, so PHP and the web server must allow at
least that or uploads fail before Laravel ever sees them — with no validation message,
because the request never arrives:

```ini
; php.ini
upload_max_filesize = 12M
post_max_size = 12M
```

```nginx
# nginx defaults to 1M
client_max_body_size 12M;
```

Keep these above `max_upload_kilobytes` in `config/ivr.php`.

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
