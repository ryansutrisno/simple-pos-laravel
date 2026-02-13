<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockHistoryFactory extends Factory
{
    protected $model = StockHistory::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $stockBefore = $product->stock;
        $quantity = fake()->numberBetween(1, 50);
        $type = fake()->randomElement(StockMovementType::cases());

        $stockAfter = in_array($type, [StockMovementType::In, StockMovementType::Adjustment])
            ? $stockBefore + $quantity
            : $stockBefore - $quantity;

        return [
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => max(0, $stockAfter),
            'reference_type' => null,
            'reference_id' => null,
            'note' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function in(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::In,
        ]);
    }

    public function out(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::Out,
        ]);
    }

    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::Sale,
        ]);
    }

    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::Adjustment,
        ]);
    }

    public function opname(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::Opname,
        ]);
    }
}
