<?php

namespace Database\Factories;

use App\Models\SplitBill;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class SplitBillFactory extends Factory
{
    protected $model = SplitBill::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'split_number' => fake()->numberBetween(1, 4),
            'subtotal' => fake()->numberBetween(10000, 100000),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'amount_paid' => fake()->numberBetween(10000, 100000),
            'reference' => null,
            'notes' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
            'reference' => fake()->uuid(),
        ]);
    }

    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'qris',
            'reference' => fake()->uuid(),
        ]);
    }
}
