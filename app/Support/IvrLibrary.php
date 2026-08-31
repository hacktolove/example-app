<?php

namespace App\Support;

use App\Models\IvrAudioFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The ordered set of IVR prompts for one service, and the files on disk that
 * back it.
 *
 * The telephony system reads a service's directory in sorted order, so the
 * zero-padded numeric filename prefix *is* the ordering contract — the
 * `position` column alone is invisible to it. This class is the only place
 * that knows that, and it keeps the two in step.
 */
final class IvrLibrary
{
    private function __construct(
        public readonly ServiceStore $service,
    ) {}

    public static function for(ServiceStore $service): self
    {
        return new self($service);
    }

    /**
     * The service's prompts in playback order.
     *
     * @return Collection<int, IvrAudioFile>
     */
    public function files(): Collection
    {
        return IvrAudioFile::query()
            ->where('service_id', $this->service->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * The directory this service's prompts live in, relative to the IVR disk.
     */
    public function directory(): string
    {
        return $this->service->package;
    }

    /**
     * Append an uploaded prompt to the end of this service's sequence.
     */
    public function add(UploadedFile $upload, ?int $uploadedBy = null): IvrAudioFile
    {
        return DB::transaction(function () use ($upload, $uploadedBy) {
            $position = (int) IvrAudioFile::query()
                ->where('service_id', $this->service->id)
                ->max('position') + 1;

            $originalName = $upload->getClientOriginalName();
            $filename = $this->filenameFor($position, $originalName);

            $this->disk()->putFileAs($this->directory(), $upload, $filename);

            return IvrAudioFile::create([
                'service_id' => $this->service->id,
                'original_name' => $originalName,
                'filename' => $filename,
                'position' => $position,
                'size_bytes' => $upload->getSize(),
                'uploaded_by' => $uploadedBy,
            ]);
        });
    }

    /**
     * Remove a prompt and close the gap it leaves in the sequence.
     */
    public function remove(IvrAudioFile $file): void
    {
        DB::transaction(function () use ($file) {
            $this->disk()->delete($this->directory().'/'.$file->filename);
            $file->delete();

            $this->resequence($this->files());
        });
    }

    /**
     * Reorder the sequence to match the given prompt ids.
     *
     * Ids not belonging to this service are ignored; any of the service's
     * prompts missing from the list keep their relative order at the end, so a
     * stale client cannot silently drop a prompt from the sequence.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            $files = $this->files()->keyBy('id');

            $ordered = collect($orderedIds)
                ->map(fn (int $id) => $files->get($id))
                ->filter()
                ->values();

            $remaining = $files->reject(fn (IvrAudioFile $f) => $ordered->contains('id', $f->id))->values();

            $this->resequence($ordered->concat($remaining));
        });
    }

    /**
     * Assign positions 1..N in the given order and make the filenames on disk
     * match.
     *
     * Renames happen in two phases via temporary names, because a reorder
     * routinely swaps two prompts and a direct rename would collide.
     *
     * @param  Collection<int, IvrAudioFile>  $files
     */
    private function resequence(Collection $files): void
    {
        $disk = $this->disk();
        $dir = $this->directory();

        $moves = [];

        foreach ($files->values() as $index => $file) {
            $position = $index + 1;
            $filename = $this->filenameFor($position, $file->original_name);

            if ($file->filename !== $filename) {
                $moves[] = ['file' => $file, 'from' => $file->filename, 'to' => $filename];
            }

            $file->update(['position' => $position, 'filename' => $filename]);
        }

        foreach ($moves as $i => $move) {
            $temp = sprintf('.reorder-%d-%s', $i, $move['to']);

            if ($disk->exists($dir.'/'.$move['from'])) {
                $disk->move($dir.'/'.$move['from'], $dir.'/'.$temp);
                $moves[$i]['temp'] = $temp;
            }
        }

        foreach ($moves as $move) {
            if (isset($move['temp'])) {
                $disk->move($dir.'/'.$move['temp'], $dir.'/'.$move['to']);
            }
        }
    }

    /**
     * `007-welcome-message.wav` — the numeric prefix is what orders playback.
     */
    private function filenameFor(int $position, string $originalName): string
    {
        $stem = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        if ($stem === '') {
            $stem = 'prompt';
        }

        return sprintf('%03d-%s.wav', $position, Str::limit($stem, 60, ''));
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('ivr.disk'));
    }
}
