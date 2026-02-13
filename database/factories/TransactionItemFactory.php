<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = fake()->numberBetween(1, 5);
        $sellingPrice = $product->selling_price;
        $purchasePrice = $product->purchase_price;

        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'profit' => ($sellingPrice - $purchasePrice) * $quantity,
            'subtotal' => $sellingPrice * $quantity,
        ];
    }
}
