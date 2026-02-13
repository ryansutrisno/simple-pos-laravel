<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameItemFactory extends Factory
{
    protected $model = StockOpnameItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create(['stock' => fake()->numberBetween(50, 100)]);
        $actualStock = fake()->numberBetween(40, 110);

        return [
            'stock_opname_id' => StockOpname::factory(),
            'product_id' => $product->id,
            'system_stock' => $product->stock,
            'actual_stock' => $actualStock,
            'difference' => $actualStock - $product->stock,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
