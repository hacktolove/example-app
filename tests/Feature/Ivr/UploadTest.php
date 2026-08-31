<?php

namespace Tests\Feature\Ivr;

use App\Models\IvrAudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ivr\Concerns\MakesTelephonyWav;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use MakesTelephonyWav;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ivr');
        $this->actingAs(User::factory()->create());
    }

    private function upload(string $contents, string $name = 'welcome.wav', int $serviceId = 1)
    {
        return $this->post('/ivr', [
            'service_id' => $serviceId,
            'file' => UploadedFile::fake()->createWithContent($name, $contents),
        ]);
    }

    public function test_accepts_a_telephony_grade_wav(): void
    {
        $response = $this->upload($this->wav());

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ivr_audio_files', [
            'service_id' => 1,
            'original_name' => 'welcome.wav',
            'filename' => '001-welcome.wav',
            'position' => 1,
        ]);

        Storage::disk('ivr')->assertExists('news/001-welcome.wav');
    }

    public function test_accepts_any_sample_rate(): void
    {
        $response = $this->upload($this->wav(sampleRate: 44100));

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, IvrAudioFile::count());
        Storage::disk('ivr')->assertExists('news/001-welcome.wav');
    }

    public function test_accepts_stereo_audio(): void
    {
        $this->upload($this->wav(channels: 2))->assertSessionHasNoErrors();

        $this->assertSame(1, IvrAudioFile::count());
    }

    public function test_accepts_any_bit_depth(): void
    {
        $this->upload($this->wav(bits: 8))->assertSessionHasNoErrors();

        $this->assertSame(1, IvrAudioFile::count());
    }

    public function test_accepts_mu_law_and_other_non_pcm_encodings(): void
    {
        $this->upload($this->wav(audioFormat: 7))->assertSessionHasNoErrors();

        $this->assertSame(1, IvrAudioFile::count());
    }

    public function test_a_configured_constraint_is_still_enforced(): void
    {
        config(['ivr.audio.channels' => [1]]);

        $this->upload($this->wav(channels: 2))->assertSessionHasErrors('file');

        $this->assertSame(0, IvrAudioFile::count());
    }

    public function test_a_configured_encoding_constraint_is_still_enforced(): void
    {
        config(['ivr.audio.encodings' => [1]]);

        $this->upload($this->wav(audioFormat: 7))->assertSessionHasErrors('file');

        $this->assertSame(0, IvrAudioFile::count());
    }

    public function test_rejects_a_non_wav_file_named_wav(): void
    {
        $response = $this->upload('ID3'.str_repeat("\x00", 128), 'fake.wav');

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, IvrAudioFile::count());
    }

    public function test_rejects_a_file_over_the_size_limit(): void
    {
        config(['ivr.max_upload_kilobytes' => 1]);

        $response = $this->upload($this->wav(frames: 4096));

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, IvrAudioFile::count());
    }

    public function test_rejects_an_unknown_service(): void
    {
        $response = $this->upload($this->wav(), serviceId: 999);

        $response->assertSessionHasErrors('service_id');
        $this->assertSame(0, IvrAudioFile::count());
    }

    public function test_uploads_append_to_the_end_of_the_sequence(): void
    {
        $this->upload($this->wav(), 'welcome.wav');
        $this->upload($this->wav(), 'menu.wav');

        $this->assertSame(
            ['001-welcome.wav', '002-menu.wav'],
            IvrAudioFile::orderBy('position')->pluck('filename')->all()
        );
    }

    public function test_each_service_keeps_its_own_sequence_and_directory(): void
    {
        $this->upload($this->wav(), 'welcome.wav', serviceId: 1);
        $this->upload($this->wav(), 'kickoff.wav', serviceId: 2);

        Storage::disk('ivr')->assertExists('news/001-welcome.wav');
        Storage::disk('ivr')->assertExists('sport/001-kickoff.wav');

        $this->assertSame(1, IvrAudioFile::where('service_id', 2)->first()->position);
    }

    public function test_records_the_uploading_user(): void
    {
        $this->upload($this->wav());

        $this->assertNotNull(IvrAudioFile::first()->uploaded_by);
    }

    public function test_guests_cannot_upload(): void
    {
        auth()->logout();

        $this->upload($this->wav())->assertRedirect('/login');
        $this->assertSame(0, IvrAudioFile::count());
    }
}
