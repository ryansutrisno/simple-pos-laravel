<?php

namespace Database\Factories;

use App\Enums\DebtStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierDebt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierDebtFactory extends Factory
{
    protected $model = SupplierDebt::class;

    public function definition(): array
    {
        $totalAmount = fake()->numberBetween(500000, 10000000);

        return [
            'debt_number' => 'HUT-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalAmount,
            'debt_date' => fake()->date(),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => DebtStatus::Pending,
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $paid = $attributes['total_amount'] / 2;

            return [
                'paid_amount' => $paid,
                'remaining_amount' => $attributes['total_amount'] - $paid,
                'status' => DebtStatus::Partial,
            ];
        });
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => $attributes['total_amount'],
            'remaining_amount' => 0,
            'status' => DebtStatus::Paid,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => DebtStatus::Overdue,
        ]);
    }
}
