<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_verified_administrator(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'admin@example.com',
            '--password' => 'Str0ng-Passw0rd!',
            '--name' => 'Ops Admin',
        ])->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Ops Admin', $user->name);
        $this->assertNotNull($user->email_verified_at, 'Admins must be verified to reach the dashboard.');
        $this->assertTrue(Hash::check('Str0ng-Passw0rd!', $user->password));
    }

    public function test_the_created_admin_can_reach_the_dashboard(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'admin@example.com',
            '--password' => 'Str0ng-Passw0rd!',
        ])->assertSuccessful();

        $this->actingAs(User::where('email', 'admin@example.com')->first())
            ->get('/ivr')
            ->assertOk();
    }

    public function test_defaults_the_name_when_none_is_given(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'admin@example.com',
            '--password' => 'Str0ng-Passw0rd!',
        ])->assertSuccessful();

        $this->assertSame('Admin', User::where('email', 'admin@example.com')->value('name'));
    }

    public function test_rejects_an_invalid_email(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'not-an-email',
            '--password' => 'Str0ng-Passw0rd!',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_rejects_a_password_that_fails_the_apps_policy(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'admin@example.com',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_updates_the_password_of_an_existing_user(): void
    {
        $existing = User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Original Name',
            'password' => Hash::make('old-password'),
        ]);

        $this->artisan('admin:create', [
            '--email' => 'admin@example.com',
            '--password' => 'Str0ng-Passw0rd!',
        ])->assertSuccessful();

        $existing->refresh();

        $this->assertTrue(Hash::check('Str0ng-Passw0rd!', $existing->password));
        $this->assertSame('Original Name', $existing->name, 'An existing name should not be overwritten.');
        $this->assertSame(1, User::count());
    }

    public function test_prompts_for_email_and_password_when_not_given(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Email address', 'prompted@example.com')
            ->expectsQuestion('Password', 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!')
            ->expectsQuestion('Name', 'Prompted Admin')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'prompted@example.com',
            'name' => 'Prompted Admin',
        ]);
    }

    public function test_rejects_a_mismatched_password_confirmation(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Email address', 'prompted@example.com')
            ->expectsQuestion('Password', 'Str0ng-Passw0rd!')
            ->expectsQuestion('Confirm password', 'Different-Passw0rd!')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_declining_the_overwrite_prompt_leaves_the_user_alone(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->artisan('admin:create')
            ->expectsQuestion('Email address', 'admin@example.com')
            ->expectsConfirmation('admin@example.com already exists. Set a new password for them?', 'no')
            ->assertFailed();

        $this->assertTrue(
            Hash::check('old-password', User::where('email', 'admin@example.com')->value('password'))
        );
    }
}
