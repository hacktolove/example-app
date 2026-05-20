<?php

namespace Database\Factories;

use App\Models\MtnSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MtnSubscription>
 */
class MtnSubscriptionFactory extends Factory
{
    protected $model = MtnSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => fake()->numberBetween(1, 5),
            'operator_id' => fake()->numberBetween(1, 3),
            'request_id' => fake()->unique()->randomNumber(8, true),
            'msisdn' => fake()->numerify('############'),
            'status' => 'ACT-SB',
            'price' => 0,
        ];
    }
}
