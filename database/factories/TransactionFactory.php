<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(10000, 500000);

        return [
            'user_id' => User::factory(),
            'customer_id' => null,
            'total' => $total,
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'cash_amount' => null,
            'change_amount' => null,
            'points_earned' => 0,
            'points_redeemed' => 0,
            'discount_from_points' => 0,
        ];
    }

    public function cash(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'] ?? fake()->numberBetween(10000, 500000);
            $cashAmount = $total + fake()->numberBetween(0, 100000);

            return [
                'payment_method' => 'cash',
                'cash_amount' => $cashAmount,
                'change_amount' => $cashAmount - $total,
            ];
        });
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
            'cash_amount' => null,
            'change_amount' => null,
        ]);
    }

    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'qris',
            'cash_amount' => null,
            'change_amount' => null,
        ]);
    }
}
