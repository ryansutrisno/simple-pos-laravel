<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'PO-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'total_amount' => fake()->numberBetween(100000, 5000000),
            'status' => PurchaseOrderStatus::Draft,
            'payment_status' => PaymentStatus::Unpaid,
            'order_date' => fake()->optional()->date(),
            'expected_date' => fake()->optional()->date(),
            'received_date' => null,
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Draft,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Pending,
            'order_date' => now(),
        ]);
    }

    public function ordered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Ordered,
            'order_date' => now()->subDays(3),
            'expected_date' => now()->addDays(7),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Received,
            'order_date' => now()->subDays(7),
            'received_date' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Cancelled,
        ]);
    }
}
