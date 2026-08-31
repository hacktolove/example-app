<?php

namespace App\Http\Controllers\Ivr;

use App\Http\Controllers\Controller;
use App\Models\IvrAudioFile;
use App\Rules\TelephonyWavFile;
use App\Support\IvrLibrary;
use App\Support\ServiceStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IvrController extends Controller
{
    /**
     * Show the prompt sequence for one service.
     */
    public function index(Request $request): Response
    {
        $services = ServiceStore::all();
        $selected = $this->resolveService($request->query('service'), $services);

        return Inertia::render('Ivr/Index', [
            'services' => collect($services)->values()->map(fn (ServiceStore $service) => [
                'id' => $service->id,
                'package' => $service->package,
                'englishName' => $service->englishName,
                'arabicName' => $service->arabicName,
            ]),
            'selectedServiceId' => $selected->id,
            'files' => IvrLibrary::for($selected)->files()->map(fn (IvrAudioFile $file) => [
                'id' => $file->id,
                'originalName' => $file->original_name,
                'filename' => $file->filename,
                'position' => $file->position,
                'sizeBytes' => $file->size_bytes,
                'uploadedAt' => $file->created_at?->toIso8601String(),
            ]),
            'audioSpec' => [
                'sampleRates' => array_values((array) config('ivr.audio.sample_rates')),
                'bitsPerSample' => array_values((array) config('ivr.audio.bits_per_sample')),
                'channels' => array_values((array) config('ivr.audio.channels')),
                'maxKilobytes' => (int) config('ivr.max_upload_kilobytes'),
            ],
        ]);
    }

    /**
     * Append an uploaded prompt to a service's sequence.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', Rule::in(array_keys(ServiceStore::all()))],
            'file' => ['required', 'file', 'max:'.(int) config('ivr.max_upload_kilobytes'), new TelephonyWavFile],
        ]);

        $service = ServiceStore::find((int) $validated['service_id']);

        IvrLibrary::for($service)->add($request->file('file'), $request->user()?->id);

        return back()->with('status', 'Prompt uploaded.');
    }

    /**
     * Set the playback order for a service's prompts.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', Rule::in(array_keys(ServiceStore::all()))],
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $service = ServiceStore::find((int) $validated['service_id']);

        IvrLibrary::for($service)->reorder($validated['order']);

        return back()->with('status', 'Order updated.');
    }

    /**
     * Remove a prompt and close the gap it leaves.
     */
    public function destroy(IvrAudioFile $ivrAudioFile): RedirectResponse
    {
        $service = ServiceStore::find($ivrAudioFile->service_id);

        if (! $service) {
            abort(404);
        }

        IvrLibrary::for($service)->remove($ivrAudioFile);

        return back()->with('status', 'Prompt removed.');
    }

    /**
     * @param  array<int, ServiceStore>  $services
     */
    private function resolveService(mixed $requested, array $services): ServiceStore
    {
        if ($requested !== null && isset($services[(int) $requested])) {
            return $services[(int) $requested];
        }

        $first = reset($services);

        abort_if($first === false, 500, 'No VAS services are configured.');

        return $first;
    }
}
