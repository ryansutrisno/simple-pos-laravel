<?php

namespace Database\Factories;

use App\Models\DebtPayment;
use App\Models\SupplierDebt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtPaymentFactory extends Factory
{
    protected $model = DebtPayment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'supplier_debt_id' => SupplierDebt::factory(),
            'amount' => fake()->numberBetween(100000, 1000000),
            'payment_date' => fake()->date(),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'note' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
