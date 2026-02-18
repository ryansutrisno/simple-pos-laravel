<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionPaymentFactory extends Factory
{
    protected $model = TransactionPayment::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'amount' => fake()->numberBetween(10000, 500000),
            'reference' => null,
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
