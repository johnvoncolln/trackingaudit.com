<?php

namespace Database\Factories;

use App\Enums\Carrier;
use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tracker>
 */
class TrackerFactory extends Factory
{
    protected $model = Tracker::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'carrier' => fake()->randomElement(Carrier::values()),
            'tracking_number' => fake()->numerify('1Z##################'),
            'reference_id' => fake()->optional()->numerify('REF-#####'),
            'reference_name' => fake()->optional()->words(2, true),
            'recipient_name' => fake()->name(),
            'recipient_email' => fake()->safeEmail(),
            'status' => TrackerStatus::IN_TRANSIT->value,
            'location' => fake()->city().', '.fake()->stateAbbr().', US',
            'status_time' => now()->subHours(fake()->numberBetween(1, 48)),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement([
                TrackerStatus::UNKNOWN->value,
                TrackerStatus::PRE_TRANSIT->value,
                TrackerStatus::IN_TRANSIT->value,
                TrackerStatus::OUT_FOR_DELIVERY->value,
                TrackerStatus::AVAILABLE_FOR_PICKUP->value,
            ]),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrackerStatus::DELIVERED->value,
            'delivery_date' => now()->subDays(2),
            'delivered_date' => now()->subDay(),
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrackerStatus::IN_TRANSIT->value,
            'delivery_date' => now()->subDays(2),
            'delivered_date' => null,
        ]);
    }

    public function needsAttention(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement([
                TrackerStatus::FAILURE->value,
                TrackerStatus::RETURN_TO_SENDER->value,
                TrackerStatus::ERROR->value,
            ]),
        ]);
    }

    public function deliveredLate(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrackerStatus::DELIVERED->value,
            'delivery_date' => now()->subDays(3),
            'delivered_date' => now()->subDay(),
        ]);
    }
}
