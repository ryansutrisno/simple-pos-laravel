<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => null,
            'type' => 'percentage',
            'value' => fake()->randomFloat(2, 5, 50),
            'min_purchase' => null,
            'max_discount' => null,
            'target_type' => 'global',
            'start_date' => now()->subDays(7),
            'end_date' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => fake()->randomFloat(2, 5000, 50000),
        ]);
    }

    public function productTarget(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'product',
        ]);
    }

    public function categoryTarget(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'category',
        ]);
    }

    public function globalTarget(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'global',
        ]);
    }

    public function voucher(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'voucher',
            'code' => strtoupper(fake()->bothify('???###')),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => now()->subDay(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->addDays(7),
        ]);
    }

    public function withLimit(int $limit = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
        ]);
    }

    public function withMinPurchase(float $amount = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'min_purchase' => $amount,
        ]);
    }

    public function withMaxDiscount(float $amount = 10000): static
    {
        return $this->state(fn (array $attributes) => [
            'max_discount' => $amount,
        ]);
    }
}
