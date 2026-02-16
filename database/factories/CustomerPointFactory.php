<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerPointFactory extends Factory
{
    protected $model = CustomerPoint::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'transaction_id' => Transaction::factory(),
            'type' => fake()->randomElement(['earn', 'redeem', 'adjust']),
            'amount' => fake()->numberBetween(10, 100),
            'balance_after' => fake()->numberBetween(100, 1000),
            'description' => fake()->sentence(),
        ];
    }

    public function earn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earn',
        ]);
    }

    public function redeem(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'redeem',
        ]);
    }

    public function adjust(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'adjust',
            'transaction_id' => null,
        ]);
    }
}
