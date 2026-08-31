<?php

namespace Tests\Feature\Ivr;

use App\Models\IvrAudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ivr\Concerns\MakesTelephonyWav;
use Tests\TestCase;

/**
 * These tests deliberately avoid Storage::fake(): faking swaps the disk out
 * entirely, so a misconfigured root — an empty IVR_AUDIO_ROOT, say — is
 * invisible to every other test in this suite.
 */
class DiskTest extends TestCase
{
    use MakesTelephonyWav;
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/ivr-'.uniqid());

        config(['filesystems.disks.ivr.root' => $this->root]);
        Storage::forgetDisk('ivr');

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        Storage::forgetDisk('ivr');

        parent::tearDown();
    }

    /**
     * A bare `IVR_AUDIO_ROOT=` in .env yields an empty string, not null, so
     * env()'s default argument does not apply — the disk root becomes '' and
     * Flysystem fails with "Unable to create a directory at .". The config file
     * is re-evaluated here because config is resolved once at bootstrap.
     */
    public function test_an_empty_env_value_falls_back_to_a_usable_root(): void
    {
        $previous = $_SERVER['IVR_AUDIO_ROOT'] ?? null;

        try {
            $_SERVER['IVR_AUDIO_ROOT'] = '';
            putenv('IVR_AUDIO_ROOT=');

            $config = require config_path('filesystems.php');
            $root = $config['disks']['ivr']['root'];

            $this->assertNotSame('', $root, 'An empty IVR_AUDIO_ROOT must fall back, not become an empty path.');
            $this->assertTrue(str_starts_with((string) $root, '/'), 'The IVR disk root must be absolute.');
        } finally {
            if ($previous === null) {
                unset($_SERVER['IVR_AUDIO_ROOT']);
                putenv('IVR_AUDIO_ROOT');
            } else {
                $_SERVER['IVR_AUDIO_ROOT'] = $previous;
                putenv('IVR_AUDIO_ROOT='.$previous);
            }
        }
    }

    public function test_an_upload_lands_on_the_real_filesystem(): void
    {
        $this->post('/ivr', [
            'service_id' => 1,
            'file' => UploadedFile::fake()
                ->createWithContent('welcome.wav', $this->wav()),
        ])->assertSessionHasNoErrors();

        $this->assertFileExists($this->root.'/news/001-welcome.wav');
    }

    public function test_the_service_directory_is_created_on_first_upload(): void
    {
        $this->assertDirectoryDoesNotExist($this->root.'/news');

        $this->post('/ivr', [
            'service_id' => 1,
            'file' => UploadedFile::fake()
                ->createWithContent('welcome.wav', $this->wav()),
        ])->assertSessionHasNoErrors();

        $this->assertDirectoryExists($this->root.'/news');
    }

    public function test_reordering_renames_files_on_the_real_filesystem(): void
    {
        foreach (['welcome.wav', 'menu.wav'] as $name) {
            $this->post('/ivr', [
                'service_id' => 1,
                'file' => UploadedFile::fake()
                    ->createWithContent($name, $this->wav()),
            ])->assertSessionHasNoErrors();
        }

        $ids = IvrAudioFile::orderBy('position')->pluck('id')->all();

        $this->put('/ivr/order', [
            'service_id' => 1,
            'order' => array_reverse($ids),
        ])->assertRedirect();

        $this->assertFileExists($this->root.'/news/001-menu.wav');
        $this->assertFileExists($this->root.'/news/002-welcome.wav');
        $this->assertFileDoesNotExist($this->root.'/news/001-welcome.wav');
    }
}
