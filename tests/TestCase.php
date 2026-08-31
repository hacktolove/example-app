<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Connections wrapped in a transaction for each test.
     *
     * This must list every service connection (see App\Support\ServiceStore)
     * and must be declared suite-wide rather than per test class. RefreshDatabase
     * migrates once per run and caches the in-memory PDO only for the connections
     * the *first* test class names; any class omitting one would leave later tests
     * with an empty, unmigrated database for it.
     *
     * @var array<int, string>
     */
    protected $connectionsToTransact = ['sqlite', 'news', 'sport'];

    protected function setUp(): void
    {
        parent::setUp();

        // Inertia pages render through the root Blade view, which references the
        // Vite manifest. Tests assert on props and responses, never on built
        // assets, so there is no reason to require a production build first.
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
