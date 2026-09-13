<?php

namespace Tests\Feature\Console;

use App\Support\ServiceStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsureVasServiceTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_a_safe_no_op_when_every_table_already_exists(): void
    {
        ServiceStore::find(1)->subscribe('+249999900046', 'api');

        $this->artisan('vasws:ensure-tables')->assertSuccessful();

        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046'], 'news');
    }

    public function test_it_creates_a_missing_profiles_table_without_touching_other_connections(): void
    {
        ServiceStore::find(1)->subscribe('+249999900046', 'api');
        Schema::connection('sport')->drop('profiles');

        $this->assertFalse(Schema::connection('sport')->hasTable('profiles'));

        $this->artisan('vasws:ensure-tables')->assertSuccessful();

        $this->assertTrue(Schema::connection('sport')->hasTable('profiles'));
        $this->assertDatabaseCount('profiles', 0, 'sport');
        $this->assertDatabaseHas('profiles', ['msisdn' => '+249999900046'], 'news');
    }

    public function test_it_creates_a_missing_history_table(): void
    {
        Schema::connection('sport')->drop('vas_subscription_history');

        $this->assertFalse(Schema::connection('sport')->hasTable('vas_subscription_history'));

        $this->artisan('vasws:ensure-tables')->assertSuccessful();

        $this->assertTrue(Schema::connection('sport')->hasTable('vas_subscription_history'));
    }
}
