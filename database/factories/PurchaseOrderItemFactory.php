<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = fake()->numberBetween(10, 100);
        $purchasePrice = $product->purchase_price;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'quantity_received' => 0,
            'purchase_price' => $purchasePrice,
            'subtotal' => $quantity * $purchasePrice,
        ];
    }

    public function received(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'quantity_received' => $attributes['quantity'] ?? fake()->numberBetween(10, 100),
            ];
        });
    }
}
