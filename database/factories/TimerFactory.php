<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Timer;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timer>
 */
class TimerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'created_by' => Client::factory(),
            'state' => Timer::STATE_RUNNING,
            'description' => fake()->sentence(),
            'is_hidden' => false,
            'started_at' => now(),
            'paused_at' => null,
            'ended_at' => null,
            'total_minutes' => 0,
        ];
    }

    /**
     * Indicate that the timer is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => Timer::STATE_RUNNING,
            'started_at' => now(),
            'paused_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the timer is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => Timer::STATE_PAUSED,
            'paused_at' => now(),
        ]);
    }

    /**
     * Indicate that the timer is stopped.
     */
    public function stopped(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => Timer::STATE_STOPPED,
            'ended_at' => now(),
        ]);
    }

    /**
     * Indicate that the timer is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => Timer::STATE_CANCELLED,
            'ended_at' => now(),
        ]);
    }

    /**
     * Indicate that the timer is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
