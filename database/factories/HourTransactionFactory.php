<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HourTransaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HourTransaction>
 */
class HourTransactionFactory extends Factory
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
            'type' => fake()->randomElement([
                HourTransaction::TYPE_CREDIT,
                HourTransaction::TYPE_DEBIT,
            ]),
            'minutes' => fake()->numberBetween(10, 300),
            'description' => fake()->sentence(),
            'internal_note' => fake()->optional()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the transaction is a credit.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HourTransaction::TYPE_CREDIT,
        ]);
    }

    /**
     * Indicate that the transaction is a debit.
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HourTransaction::TYPE_DEBIT,
        ]);
    }

    /**
     * Indicate that the transaction is a transfer in.
     */
    public function transferIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HourTransaction::TYPE_TRANSFER_IN,
        ]);
    }

    /**
     * Indicate that the transaction is a transfer out.
     */
    public function transferOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HourTransaction::TYPE_TRANSFER_OUT,
        ]);
    }
}
