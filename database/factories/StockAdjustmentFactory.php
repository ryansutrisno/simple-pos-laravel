<?php

namespace Database\Factories;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        return [
            'adjustment_number' => 'ADJ-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'adjustment_date' => fake()->date(),
            'type' => fake()->randomElement(AdjustmentType::cases()),
            'reason' => fake()->randomElement(AdjustmentReason::cases()),
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function increase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AdjustmentType::Increase,
        ]);
    }

    public function decrease(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AdjustmentType::Decrease,
        ]);
    }
}
