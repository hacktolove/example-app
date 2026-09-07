<?php

namespace Tests\Feature\Ivr;

use App\Models\IvrAudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ivr\Concerns\MakesTelephonyWav;
use Tests\TestCase;

class PlaybackTest extends TestCase
{
    use MakesTelephonyWav;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ivr');
        $this->actingAs(User::factory()->create());
    }

    public function test_it_streams_the_prompt_audio(): void
    {
        $this->uploadPrompt('welcome.wav', contents: $this->wav());

        $file = IvrAudioFile::sole();

        $response = $this->get("/ivr/{$file->id}/audio");

        $response->assertOk();
        $this->assertSame(
            Storage::disk('ivr')->get('news/'.$file->filename),
            $response->streamedContent(),
        );
    }

    public function test_it_404s_for_a_prompt_whose_service_no_longer_exists(): void
    {
        $file = IvrAudioFile::create([
            'service_id' => 999,
            'original_name' => 'welcome.wav',
            'filename' => '001-welcome.wav',
            'position' => 1,
            'size_bytes' => 10,
            'uploaded_by' => null,
        ]);

        $this->get("/ivr/{$file->id}/audio")->assertNotFound();
    }

    public function test_guests_cannot_play_a_prompt(): void
    {
        $this->uploadPrompt('welcome.wav');
        $file = IvrAudioFile::sole();

        auth()->logout();

        $this->get("/ivr/{$file->id}/audio")->assertRedirect('/login');
    }
}
