<?php

namespace Database\Factories;

use App\Models\ReceiptTemplate;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptTemplateFactory extends Factory
{
    protected $model = ReceiptTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Template',
            'description' => fake()->sentence(),
            'template_data' => [
                'header' => [
                    'show_logo' => false,
                    'show_store_name' => true,
                    'show_address' => true,
                    'show_phone' => true,
                ],
                'body' => [
                    'show_item_details' => true,
                    'show_subtotal' => true,
                    'show_payment_method' => true,
                ],
                'footer' => [
                    'show_thank_you' => true,
                    'show_datetime' => true,
                    'show_cashier' => true,
                ],
            ],
            'is_default' => false,
            'is_active' => true,
            'store_id' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forStore(Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $store->id,
        ]);
    }
}
