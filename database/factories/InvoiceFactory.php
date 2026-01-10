<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minutes = fake()->numberBetween(60, 600);
        $pricePerHour = fake()->randomFloat(2, 50, 200);
        $totalAmount = round(($minutes / 60) * $pricePerHour, 2);

        return [
            'wallet_id' => Wallet::factory(),
            'status' => Invoice::STATUS_OPEN,
            'minutes' => $minutes,
            'price_per_hour' => $pricePerHour,
            'total_amount' => $totalAmount,
            'client_message' => fake()->optional()->sentence(),
            'paid_at' => null,
        ];
    }

    /**
     * Indicate that the invoice is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_OPEN,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_CANCELLED,
            'paid_at' => null,
        ]);
    }
}
