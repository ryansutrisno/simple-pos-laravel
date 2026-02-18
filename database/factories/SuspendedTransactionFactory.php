<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SuspendedTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuspendedTransactionFactory extends Factory
{
    protected $model = SuspendedTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'suspension_key' => SuspendedTransaction::generateSuspensionKey(),
            'customer_id' => null,
            'cart_items' => [],
            'subtotal' => fake()->numberBetween(10000, 500000),
            'discount_amount' => 0,
            'total' => fake()->numberBetween(10000, 500000),
            'voucher_code' => null,
            'notes' => null,
        ];
    }

    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    public function withCartItems(int $count = 2): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $items = [];
            for ($i = 0; $i < $count; $i++) {
                $items[] = [
                    'product_id' => fake()->numberBetween(1, 100),
                    'name' => fake()->word(),
                    'purchase_price' => fake()->numberBetween(5000, 20000),
                    'selling_price' => fake()->numberBetween(10000, 50000),
                    'quantity' => fake()->numberBetween(1, 5),
                    'profit' => fake()->numberBetween(1000, 10000),
                ];
            }

            return [
                'cart_items' => $items,
            ];
        });
    }
}
