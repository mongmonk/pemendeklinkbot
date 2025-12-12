<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'short_code' => $this->faker->unique()->regexify('[a-zA-Z0-9_-]{5,10}'),
            'long_url' => $this->faker->url(),
            'is_custom' => false,
            'telegram_user_id' => $this->faker->numberBetween(100000000, 999999999),
            'clicks' => $this->faker->numberBetween(0, 1000),
            'disabled' => false,
            'disable_reason' => null,
            'disabled_at' => null,
        ];
    }

    /**
     * Indicate that the link is custom.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_custom' => true,
        ]);
    }

    /**
     * Indicate that the link is disabled.
     */
    public function disabled(string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'disabled' => true,
            'disable_reason' => $reason ?? $this->faker->sentence(),
            'disabled_at' => now(),
        ]);
    }

    /**
     * Indicate that the link is popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => $this->faker->numberBetween(1000, 10000),
        ]);
    }
}