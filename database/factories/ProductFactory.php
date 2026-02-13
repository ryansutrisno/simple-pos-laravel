<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $purchasePrice = fake()->numberBetween(5000, 50000);

        return [
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'description' => fake()->sentence(),
            'purchase_price' => $purchasePrice,
            'selling_price' => $purchasePrice + fake()->numberBetween(1000, 20000),
            'stock' => fake()->numberBetween(10, 100),
            'barcode' => fake()->unique()->numerify('############'),
            'image' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(1, 5),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
