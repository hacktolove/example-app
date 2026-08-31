<?php

namespace Tests\Feature\Ivr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ivr\Concerns\MakesTelephonyWav;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use MakesTelephonyWav;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ivr');
        $this->actingAs(User::factory()->create());
    }

    public function test_lists_every_configured_service(): void
    {
        $this->get('/ivr')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ivr/Index')
                ->has('services', 2)
                ->where('services.0.englishName', 'News')
                ->where('services.1.englishName', 'Sport')
            );
    }

    public function test_defaults_to_the_first_service(): void
    {
        $this->get('/ivr')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedServiceId', 1));
    }

    public function test_shows_only_the_selected_services_prompts_in_order(): void
    {
        $this->uploadPrompt('welcome.wav', serviceId: 1);
        $this->uploadPrompt('menu.wav', serviceId: 1);
        $this->uploadPrompt('kickoff.wav', serviceId: 2);

        $this->get('/ivr?service=2')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selectedServiceId', 2)
                ->has('files', 1)
                ->where('files.0.originalName', 'kickoff.wav')
                ->where('files.0.filename', '001-kickoff.wav')
            );
    }

    public function test_falls_back_to_the_first_service_for_an_unknown_id(): void
    {
        $this->get('/ivr?service=999')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedServiceId', 1));
    }

    public function test_exposes_the_accepted_audio_spec(): void
    {
        $this->get('/ivr')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('audioSpec.sampleRates', [])
                ->where('audioSpec.bitsPerSample', [])
                ->where('audioSpec.channels', [])
                ->where('audioSpec.maxKilobytes', 10240)
            );
    }

    public function test_the_spec_reflects_a_configured_constraint(): void
    {
        config(['ivr.audio.channels' => [1]]);

        $this->get('/ivr')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('audioSpec.channels', [1]));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/ivr')->assertRedirect('/login');
    }
}
