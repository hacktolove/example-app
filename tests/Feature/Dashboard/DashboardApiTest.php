<?php

namespace Tests\Feature\Dashboard;

use App\Models\MtnSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── KPI endpoint ────────────────────────────────────────────────────────

    public function test_kpi_returns_200_with_correct_structure_for_date_with_data(): void
    {
        $user = User::factory()->create();
        $date = '2026-05-10';

        MtnSubscription::factory()->create([
            'status' => 'ACT-SB',
            'msisdn' => '249111111111',
            'price' => 0,
            'created_at' => $date.' 10:00:00',
        ]);

        MtnSubscription::factory()->create([
            'status' => 'FSC-BL',
            'msisdn' => '249111111111',
            'price' => 500.00,
            'created_at' => $date.' 11:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/kpi?date='.$date);

        $response->assertOk()
            ->assertJsonStructure([
                'date',
                'currency',
                'summary' => [
                    'revenue',
                    'activeSubscribers',
                    'newSubscribers',
                    'churn',
                    'chargeSuccessRate',
                    'uniqueCharged',
                ],
                'charging' => [
                    'uniqueCharged',
                    'successfulCharges',
                    'failedCharges',
                ],
            ])
            ->assertJsonFragment(['date' => $date, 'currency' => 'SDG']);
    }

    public function test_kpi_returns_all_zeros_for_date_with_no_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/dashboard/kpi?date=2000-01-01');

        $response->assertOk()
            ->assertJson([
                'date' => '2000-01-01',
                'currency' => 'SDG',
                'summary' => [
                    'revenue' => 0,
                    'activeSubscribers' => 0,
                    'newSubscribers' => 0,
                    'churn' => 0,
                    'chargeSuccessRate' => 0,
                    'uniqueCharged' => 0,
                ],
                'charging' => [
                    'uniqueCharged' => 0,
                    'successfulCharges' => 0,
                    'failedCharges' => 0,
                ],
            ]);
    }

    public function test_kpi_defaults_to_today_when_date_param_is_absent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/dashboard/kpi');

        $response->assertOk()
            ->assertJsonFragment(['date' => Carbon::today()->toDateString()]);
    }

    public function test_kpi_returns_401_for_unauthenticated_requests(): void
    {
        $this->getJson('/api/dashboard/kpi')->assertUnauthorized();
    }

    public function test_active_subscribers_correctly_subtracts_bld_sb_events(): void
    {
        $user = User::factory()->create();
        $date = '2026-05-10';

        // 3 subscribers created before or on the date
        MtnSubscription::factory()->count(3)->create([
            'status' => 'ACT-SB',
            'price' => 0,
            'created_at' => $date.' 08:00:00',
        ]);

        // 1 unsubscribe on the same day
        MtnSubscription::factory()->create([
            'status' => 'BLD-SB',
            'price' => 0,
            'created_at' => $date.' 09:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/kpi?date='.$date);

        $response->assertOk()
            ->assertJsonPath('summary.activeSubscribers', 2);
    }

    public function test_charge_success_rate_is_zero_when_active_subscribers_is_zero(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/dashboard/kpi?date=2000-01-01');

        $response->assertOk()
            ->assertJsonPath('summary.chargeSuccessRate', 0);
    }

    // ─── Trend endpoint ───────────────────────────────────────────────────────

    public function test_trend_returns_30_data_points_by_default_with_zero_filled_gaps(): void
    {
        $user = User::factory()->create();

        // Create a single data point somewhere in the range
        MtnSubscription::factory()->create([
            'status' => 'ACT-SB',
            'price' => 0,
            'created_at' => Carbon::today()->subDays(5)->toDateString().' 10:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/trend?metric=new_subscribers');

        $response->assertOk();

        $points = $response->json('points');

        $this->assertCount(30, $points);

        // All points that don't match the seed should be 0
        $nonZeroCount = collect($points)->filter(fn ($p) => $p['value'] > 0)->count();
        $this->assertGreaterThanOrEqual(1, $nonZeroCount);

        // Every gap date should have value 0
        $zeroCount = collect($points)->filter(fn ($p) => $p['value'] === 0)->count();
        $this->assertGreaterThanOrEqual(29, $zeroCount);
    }

    public function test_trend_returns_422_for_unknown_metric(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/dashboard/trend?metric=invalid_metric')
            ->assertUnprocessable();
    }

    public function test_revenue_trend_sums_only_fsc_bl_and_rsc_bl_rows(): void
    {
        $user = User::factory()->create();
        $date = Carbon::today()->subDays(2)->toDateString();

        MtnSubscription::factory()->create([
            'status' => 'FSC-BL',
            'price' => 300.00,
            'msisdn' => '249111111001',
            'created_at' => $date.' 10:00:00',
        ]);

        MtnSubscription::factory()->create([
            'status' => 'RSC-BL',
            'price' => 200.00,
            'msisdn' => '249111111002',
            'created_at' => $date.' 11:00:00',
        ]);

        // This should NOT be counted
        MtnSubscription::factory()->create([
            'status' => 'FFL-BL',
            'price' => 999.00,
            'msisdn' => '249111111003',
            'created_at' => $date.' 12:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/trend?metric=revenue&days=30');

        $response->assertOk();

        $points = $response->json('points');
        $point = collect($points)->firstWhere('date', $date);

        $this->assertNotNull($point);
        $this->assertEquals(500.0, $point['value']);
    }

    public function test_churn_trend_counts_distinct_msisdns_per_day(): void
    {
        $user = User::factory()->create();
        $date = Carbon::today()->subDays(3)->toDateString();

        // Same msisdn appears twice — should count as 1
        MtnSubscription::factory()->create([
            'status' => 'BLD-SB',
            'price' => 0,
            'msisdn' => '249111111111',
            'created_at' => $date.' 09:00:00',
        ]);
        MtnSubscription::factory()->create([
            'status' => 'BLD-SB',
            'price' => 0,
            'msisdn' => '249111111111',
            'created_at' => $date.' 10:00:00',
        ]);

        // Different msisdn — should count as 1 more
        MtnSubscription::factory()->create([
            'status' => 'BLD-SB',
            'price' => 0,
            'msisdn' => '249222222222',
            'created_at' => $date.' 11:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/trend?metric=churn&days=30');

        $response->assertOk();

        $points = $response->json('points');
        $point = collect($points)->firstWhere('date', $date);

        $this->assertNotNull($point);
        $this->assertEquals(2, $point['value']);
    }

    public function test_trend_returns_401_for_unauthenticated_requests(): void
    {
        $this->getJson('/api/dashboard/trend?metric=revenue')->assertUnauthorized();
    }
}
