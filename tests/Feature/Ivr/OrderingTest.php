<?php

namespace Tests\Feature\Ivr;

use App\Models\IvrAudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ivr\Concerns\MakesTelephonyWav;
use Tests\TestCase;

class OrderingTest extends TestCase
{
    use MakesTelephonyWav;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ivr');
        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array<int, int> ids in current order
     */
    private function seedThreePrompts(): array
    {
        $this->uploadPrompt('welcome.wav');
        $this->uploadPrompt('menu.wav');
        $this->uploadPrompt('goodbye.wav');

        return IvrAudioFile::orderBy('position')->pluck('id')->all();
    }

    private function filenamesInOrder(int $serviceId = 1): array
    {
        return IvrAudioFile::where('service_id', $serviceId)
            ->orderBy('position')
            ->pluck('filename')
            ->all();
    }

    public function test_reorder_renumbers_positions_and_filenames(): void
    {
        [$welcome, $menu, $goodbye] = $this->seedThreePrompts();

        $this->put('/ivr/order', [
            'service_id' => 1,
            'order' => [$goodbye, $welcome, $menu],
        ])->assertRedirect();

        $this->assertSame(
            ['001-goodbye.wav', '002-welcome.wav', '003-menu.wav'],
            $this->filenamesInOrder()
        );
    }

    public function test_reorder_renames_the_files_on_disk(): void
    {
        [$welcome, $menu, $goodbye] = $this->seedThreePrompts();

        $this->put('/ivr/order', [
            'service_id' => 1,
            'order' => [$goodbye, $welcome, $menu],
        ]);

        $disk = Storage::disk('ivr');
        $disk->assertExists('news/001-goodbye.wav');
        $disk->assertExists('news/002-welcome.wav');
        $disk->assertExists('news/003-menu.wav');

        $disk->assertMissing('news/001-welcome.wav');
        $disk->assertMissing('news/002-menu.wav');
        $disk->assertMissing('news/003-goodbye.wav');
    }

    public function test_swapping_two_prompts_does_not_collide(): void
    {
        [$welcome, $menu] = $this->seedThreePrompts();

        // A straight swap is the case where a naive rename would clobber a file.
        $this->put('/ivr/order', [
            'service_id' => 1,
            'order' => [$menu, $welcome],
        ])->assertRedirect();

        $this->assertSame(
            ['001-menu.wav', '002-welcome.wav', '003-goodbye.wav'],
            $this->filenamesInOrder()
        );

        Storage::disk('ivr')->assertExists('news/001-menu.wav');
        Storage::disk('ivr')->assertExists('news/002-welcome.wav');
    }

    public function test_prompts_missing_from_the_order_keep_their_place_at_the_end(): void
    {
        [$welcome, $menu, $goodbye] = $this->seedThreePrompts();

        $this->put('/ivr/order', [
            'service_id' => 1,
            'order' => [$goodbye],
        ])->assertRedirect();

        $this->assertSame(
            ['001-goodbye.wav', '002-welcome.wav', '003-menu.wav'],
            $this->filenamesInOrder()
        );
        $this->assertSame(3, IvrAudioFile::count());
    }

    public function test_reorder_ignores_ids_belonging_to_another_service(): void
    {
        $this->uploadPrompt('welcome.wav', serviceId: 1);
        $this->uploadPrompt('kickoff.wav', serviceId: 2);

        $sportId = IvrAudioFile::where('service_id', 2)->value('id');
        $newsId = IvrAudioFile::where('service_id', 1)->value('id');

        $this->put('/ivr/order', [
            'service_id' => 1,
            'order' => [$sportId, $newsId],
        ])->assertRedirect();

        $this->assertSame(['001-welcome.wav'], $this->filenamesInOrder(1));
        $this->assertSame(['001-kickoff.wav'], $this->filenamesInOrder(2));
    }

    public function test_deleting_closes_the_gap_in_the_sequence(): void
    {
        [$welcome, $menu] = $this->seedThreePrompts();

        $this->delete("/ivr/{$welcome}")->assertRedirect();

        $this->assertSame(
            ['001-menu.wav', '002-goodbye.wav'],
            $this->filenamesInOrder()
        );

        $disk = Storage::disk('ivr');
        $disk->assertMissing('news/001-welcome.wav');
        $disk->assertExists('news/001-menu.wav');
        $disk->assertExists('news/002-goodbye.wav');
    }

    public function test_deleting_leaves_other_services_untouched(): void
    {
        $this->uploadPrompt('welcome.wav', serviceId: 1);
        $this->uploadPrompt('kickoff.wav', serviceId: 2);

        $newsId = IvrAudioFile::where('service_id', 1)->value('id');

        $this->delete("/ivr/{$newsId}")->assertRedirect();

        $this->assertSame([], $this->filenamesInOrder(1));
        $this->assertSame(['001-kickoff.wav'], $this->filenamesInOrder(2));
        Storage::disk('ivr')->assertExists('sport/001-kickoff.wav');
    }

    public function test_reorder_rejects_an_unknown_service(): void
    {
        $this->seedThreePrompts();

        $this->put('/ivr/order', ['service_id' => 999, 'order' => []])
            ->assertSessionHasErrors('service_id');
    }

    public function test_guests_cannot_reorder_or_delete(): void
    {
        [$welcome] = $this->seedThreePrompts();

        auth()->logout();

        $this->put('/ivr/order', ['service_id' => 1, 'order' => [$welcome]])
            ->assertRedirect('/login');
        $this->delete("/ivr/{$welcome}")->assertRedirect('/login');

        $this->assertSame(3, IvrAudioFile::count());
    }
}
