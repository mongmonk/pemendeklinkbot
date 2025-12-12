<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'enabled', 'disabled', 'login', 'logout']),
            'model_type' => fake()->randomElement(['App\Models\Link', 'App\Models\Admin']),
            'model_id' => fake()->randomNumber(),
            'description' => fake()->sentence(),
            'properties' => [],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the activity log is for a link.
     */
    public function forLink(Link $link): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'model_type' => Link::class,
            'model_id' => $link->id,
        ]));
    }

    /**
     * Indicate that the activity log is for an admin.
     */
    public function forAdmin(Admin $admin): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'model_type' => Admin::class,
            'model_id' => $admin->id,
        ]));
    }

    /**
     * Indicate that the activity log is a creation action.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'action' => 'created',
            'description' => 'Created new record',
        ]));
    }

    /**
     * Indicate that the activity log is an update action.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'action' => 'updated',
            'description' => 'Updated existing record',
        ]));
    }

    /**
     * Indicate that the activity log is a deletion action.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'action' => 'deleted',
            'description' => 'Deleted record',
        ]));
    }

    /**
     * Indicate that the activity log is a login action.
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'action' => 'login',
            'description' => 'User logged in',
        ]));
    }
}