<?php

namespace Tests\Feature\Vasws;

use App\Models\Profile;
use App\Support\ServiceStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Each VAS service owns its own database. These tests pin down the boundary
 * itself rather than any one endpoint's behaviour.
 */
class ServiceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_service_resolves_to_its_own_connection(): void
    {
        $this->assertSame('news', ServiceStore::find(1)->connection);
        $this->assertSame('sport', ServiceStore::find(2)->connection);
        $this->assertSame(['news', 'sport'], ServiceStore::connections());
    }

    public function test_unknown_serviceid_resolves_to_null(): void
    {
        $this->assertNull(ServiceStore::find(999));
    }

    public function test_each_service_database_is_genuinely_separate(): void
    {
        ServiceStore::find(1)->subscribe('+249999900046', 'api');

        $this->assertDatabaseCount('profiles', 1, 'news');
        $this->assertDatabaseCount('profiles', 0, 'sport');
    }

    public function test_a_subscriber_can_be_active_in_both_services_at_once(): void
    {
        $news = ServiceStore::find(1);
        $sport = ServiceStore::find(2);

        $news->subscribe('+249999900046', 'api');
        $sport->subscribe('+249999900046', 'vasws');

        $this->assertTrue($news->isSubscribed('+249999900046'));
        $this->assertTrue($sport->isSubscribed('+249999900046'));
    }

    public function test_unsubscribing_one_service_leaves_the_other_active(): void
    {
        $news = ServiceStore::find(1);
        $sport = ServiceStore::find(2);

        $news->subscribe('+249999900046', 'api');
        $sport->subscribe('+249999900046', 'vasws');

        $this->assertTrue($sport->unsubscribe('+249999900046', 'vasws'));

        $this->assertTrue($news->isSubscribed('+249999900046'));
        $this->assertFalse($sport->isSubscribed('+249999900046'));
    }

    public function test_unsubscribe_reports_false_when_not_registered(): void
    {
        $this->assertFalse(ServiceStore::find(2)->unsubscribe('+249999900046', 'vasws'));
    }

    public function test_history_is_scoped_to_the_service_that_wrote_it(): void
    {
        $news = ServiceStore::find(1);
        $sport = ServiceStore::find(2);

        $news->subscribe('+249999900046', 'api');
        $news->unsubscribe('+249999900046', 'api');

        $this->assertCount(1, $news->history('+249999900046'));
        $this->assertCount(0, $sport->history('+249999900046'));
    }

    public function test_querying_a_profile_without_a_service_connection_fails_loudly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Profile has no service connection');

        Profile::query()->count();
    }
}
