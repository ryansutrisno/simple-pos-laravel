<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentItemFactory extends Factory
{
    protected $model = StockAdjustmentItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();

        return [
            'stock_adjustment_id' => StockAdjustment::factory(),
            'product_id' => $product->id,
            'quantity' => fake()->numberBetween(1, 50),
            'stock_before' => $product->stock,
            'stock_after' => $product->stock,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
